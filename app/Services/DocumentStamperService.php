<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Tcpdf\Fpdi;

class DocumentStamperService
{
   /**
     * İndirilen PDF'i %90 küçültüp aşağı kaydırır ve en tepeye ISO Antetini basar. (Sıfır Çakışma)
     */
    public function stampPdf(Document $document): string
    {
        $version = $document->currentVersion;

        if (!$version || empty($version->file_path)) {
            throw new \Exception("Bu belgeye ait fiziksel bir dosya bulunamadı.");
        }

        $filePath = Storage::disk('local')->path($version->file_path);

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($filePath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], $size);

            // --- 1. MÜHENDİSLİK HARİKASI: KÜÇÜLT VE AŞAĞI KAYDIR ---
            $scale = 0.88; // Orijinal belgeyi %88 oranında küçült
            $newW = $size['width'] * $scale;
            $newH = $size['height'] * $scale;
            
            $xOffset = ($size['width'] - $newW) / 2; // Sayfayı yatayda tam ortala
            $yOffset = 32; // Tepeden 3.2 cm boşluk bırak! (İşte antet buraya gelecek)

            // Orijinal sayfayı yeni boyutları ve yeni konumuyla bas
            $pdf->useTemplate($templateId, $xOffset, $yOffset, $newW, $newH);

            // --- 2. ANTET ÇİZİMİ (BOŞ KALAN TEPE NOKTASINA) ---
            $pdf->SetAlpha(1);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetLineWidth(0.3);

            $x = 10; 
            $y = 6; // Sayfanın en tepesinden 6 birim aşağıya çiz (Y=32'ye kadar güvenli alanımız var)
            $h = 22; 
            
            $w1 = 40; // Logo
            $w2 = 90; // Başlık
            $w3 = 30; // Etiketler
            $w4 = 30; // Değerler

            // Logo Hücresi
            $pdf->SetXY($x, $y);
            $pdf->Cell($w1, $h, '', 1, 0, 'C', true); 
            $logoPath = public_path('assets/images/koksan-logo.jpg');
            if (file_exists($logoPath)) {
                $pdf->Image($logoPath, $x + 2, $y + 2, $w1 - 4, $h - 4, '', '', '', false, 300, '', false, false, 0, 'CM');
            } else {
                $pdf->SetXY($x, $y);
                $pdf->SetFont('dejavusans', 'B', 16);
                $pdf->SetTextColor(30, 58, 138); 
                $pdf->Cell($w1, $h, "KÖKSAN", 0, 0, 'C', false);
            }

            // Başlık Hücresi
            $pdf->SetTextColor(0, 0, 0); 
            $title = mb_strtoupper($document->title, 'UTF-8');
            $pdf->SetFont('dejavusans', 'B', mb_strlen($title) > 60 ? 7 : 9);
            $pdf->MultiCell($w2, $h, $title, 1, 'C', true, 0, $x + $w1, $y, true, 0, false, true, $h, 'M');

            // Bilgi Satırları
            $publishDate = $document->created_at->format('d.m.Y');
            $revNo = $version->version_number ?? '0';
            $revDate = $version->created_at->format('d.m.Y');
            $rowH = $h / 4; 

            // 1. Satır
            $pdf->SetXY($x + $w1 + $w2, $y);
            $pdf->SetFont('dejavusans', '', 7);
            $pdf->Cell($w3, $rowH, ' Doküman No', 1, 0, 'L', true);
            $pdf->SetFont('dejavusans', 'B', 7);
            $pdf->Cell($w4, $rowH, ' ' . $document->document_number, 1, 0, 'L', true);

            // 2. Satır
            $pdf->SetXY($x + $w1 + $w2, $y + $rowH);
            $pdf->SetFont('dejavusans', '', 7);
            $pdf->Cell($w3, $rowH, ' Yayın Tarihi', 1, 0, 'L', true);
            $pdf->SetFont('dejavusans', 'B', 7);
            $pdf->Cell($w4, $rowH, ' ' . $publishDate, 1, 0, 'L', true);

            // 3. Satır
            $pdf->SetXY($x + $w1 + $w2, $y + ($rowH * 2));
            $pdf->SetFont('dejavusans', '', 7);
            $pdf->Cell($w3, $rowH, ' Rev. No / Tarihi', 1, 0, 'L', true);
            $pdf->SetFont('dejavusans', 'B', 7);
            $pdf->Cell($w4, $rowH, ' ' . $revNo . ' / ' . $revDate, 1, 0, 'L', true);

            // 4. Satır
            $pdf->SetXY($x + $w1 + $w2, $y + ($rowH * 3));
            $pdf->SetFont('dejavusans', '', 7);
            $pdf->Cell($w3, $rowH, ' Sayfa No', 1, 0, 'L', true);
            $pdf->SetFont('dejavusans', 'B', 7);
            $pdf->Cell($w4, $rowH, ' ' . $pageNo . ' / ' . $pageCount, 1, 0, 'L', true);
        }

        return $pdf->Output('', 'S');
    }
}
