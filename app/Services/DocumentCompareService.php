<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use Exception;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as PhpWordIOFactory;
use Jfcherng\Diff\DiffHelper;

class DocumentCompareService
{
    /**
     * İki versiyonu karşılaştırır ve hem metin hem de görsel diff için gerekli verileri döner.
     *
     * @param Document $document
     * @param DocumentVersion $oldVersion
     * @param DocumentVersion $newVersion
     * @return array
     * @throws Exception
     */
    public function compare(Document $document, DocumentVersion $oldVersion, DocumentVersion $newVersion): array
    {
        // 1. Fiziksel dosya yollarını al
        $oldFilePath = Storage::disk('local')->path($oldVersion->file_path);
        $newFilePath = Storage::disk('local')->path($newVersion->file_path);

        if (!file_exists($oldFilePath) || !file_exists($newFilePath)) {
            throw new Exception("Karşılaştırılacak fiziksel dosyalardan biri veya ikisi bulunamadı.");
        }

        // 2. Dosya türünü tespit et (Mime type veya uzantı)
        $oldExt = strtolower(pathinfo($oldFilePath, PATHINFO_EXTENSION));
        $newExt = strtolower(pathinfo($newFilePath, PATHINFO_EXTENSION));

        if ($oldExt !== $newExt) {
            throw new Exception("Farklı formattaki dosyalar karşılaştırılamaz (Örn: PDF ile DOCX).");
        }

        // 3. Metinleri Çıkar
        $oldText = $this->extractText($oldFilePath, $oldExt);
        $newText = $this->extractText($newFilePath, $newExt);

        // 4. Metin Ön İşleme: Devasa paragrafları cümlelere bölme
        // PDF veya Word'den çıkarılan metinlerde diff2html'in düzgün Side-by-Side çalışabilmesi için
        // Nokta ve boşluktan (. ) sonra satır sonu (\n) ekliyoruz.
        $oldText = preg_replace('/\. /', ".\n", $oldText);
        $newText = preg_replace('/\. /', ".\n", $newText);

        // 5. Metinleri Karşılaştır (Unified Diff)
        $unifiedDiff = $this->generateUnifiedDiff($oldText, $newText);

        // 5. Görsel Mod İçin Dosya İndirme URL'lerini Hazırla (Frontend PDF.js için)
        // routes/web.php'deki 'documents.download' rotasını kullanacağız
        $oldFileUrl = route('documents.download', ['document' => $document->id, 'v' => $oldVersion->id]);
        $newFileUrl = route('documents.download', ['document' => $document->id, 'v' => $newVersion->id]);

        return [
            'success' => true,
            'extension' => $oldExt,
            'text_diff' => $unifiedDiff,
            'visual_diff' => [
                'old_file_url' => $oldFileUrl,
                'new_file_url' => $newFileUrl,
            ],
        ];
    }

    /**
     * Dosyadan ham metni çıkarır.
     */
    private function extractText(string $filePath, string $extension): string
    {
        try {
            if ($extension === 'pdf') {
                $parser = new PdfParser();
                $pdf = $parser->parseFile($filePath);
                return $pdf->getText();
            } elseif (in_array($extension, ['doc', 'docx'])) {
                $phpWord = PhpWordIOFactory::load($filePath);
                $text = '';
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $text .= $element->getText() . "\n";
                        } elseif (method_exists($element, 'getElements')) {
                            // TextRun gibi iç içe elementleri olan yapılar için
                            foreach ($element->getElements() as $subElement) {
                                if (method_exists($subElement, 'getText')) {
                                    $text .= $subElement->getText();
                                }
                            }
                            $text .= "\n";
                        }
                    }
                }
                return $text;
            } elseif (in_array($extension, ['txt', 'html'])) {
                // Sadece HTML taglarını temizleyip ham metni al (İsteğe bağlı)
                return strip_tags(file_get_contents($filePath));
            }
            
            return ""; // Desteklenmeyen formattaysa boş döner (Görsel mod çalışmaya devam etsin diye hata fırlatmıyoruz)
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Metin çıkarma hatası ({$extension}): " . $e->getMessage());
            return "";
        }
    }

    /**
     * jfcherng/php-diff kütüphanesini kullanarak Unified Diff oluşturur.
     */
    private function generateUnifiedDiff(string $oldText, string $newText): string
    {
        // Satır bazlı array'e çevir
        $oldLines = explode("\n", str_replace(["\r\n", "\r"], "\n", $oldText));
        $newLines = explode("\n", str_replace(["\r\n", "\r"], "\n", $newText));

        // Diff seçenekleri
        $diffOptions = [
            'context' => \Jfcherng\Diff\Differ::CONTEXT_ALL,
            'ignoreCase' => false,
            'ignoreWhitespace' => false,
        ];
        
        // Render seçenekleri (Unified diff formatında çıktı almak istiyoruz)
        $rendererOptions = [
            'detailLevel' => 'word', // Kelime bazlı detay
            'language' => 'eng',
        ];

        // diff2html, "Unified Diff" (yama) formatını sever. 
        // php-diff kütüphanesinin "Unified" renderer'ını kullanarak string üretiyoruz.
        $result = DiffHelper::calculate($oldLines, $newLines, 'Unified', $diffOptions, $rendererOptions);
        
        // diff2html'in dosyayı algılayabilmesi için git formatında dummy header ekliyoruz
        if (!empty($result)) {
            $result = "--- a/Eski_Versiyon\n+++ b/Guncel_Versiyon\n" . $result;
        }

        return $result;
    }
}
