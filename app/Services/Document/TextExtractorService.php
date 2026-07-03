<?php

namespace App\Services\Document;

use Exception;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Storage;
/**
 * Bu servis, Single Responsibility (Tek Sorumluluk) prensibi gereği sadece fiziksel dosyalardan
 * saf (plain) metni çıkarmakla ilgilenir. Mime türüne göre doğru ayrıştırıcıyı (parser) devreye alır.
 */
class TextExtractorService
{
    /**
     * Verilen dosya yolundan MIME türüne göre metni okur ve çıkartır.
     *
     * @param string $filePath Storage içindeki dosya yolu
     * @param string $mimeType Dosyanın MIME türü
     * @return string Çıkartılan saf metin
     * @throws Exception Desteklenmeyen format veya okuma hatası
     */
    public function extract(string $filePath, string $mimeType): string
    {
        // Kurumsal Güvenlik: Dosyalar public klasörde değil, storage/app altında korunuyor.
        $absolutePath = Storage::path($filePath);

        if (!file_exists($absolutePath)) {
            throw new Exception("Dosya bulunamadı: {$absolutePath}");
        }

        return match ($mimeType) {
            'application/pdf' => $this->extractFromPdf($absolutePath),
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
            'application/msword' => $this->extractFromWord($absolutePath),
            'text/plain' => file_get_contents($absolutePath),
            default => throw new Exception("RAG Sistemi için desteklenmeyen dosya formatı: {$mimeType}"),
        };
    }

    /**
     * Saf PHP tabanlı parser ile PDF içeriğini okur.
     */
    protected function extractFromPdf(string $path): string
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($path);
        
        return $pdf->getText();
    }

    /**
     * PHPWord kütüphanesi ile Word dokümanlarının içeriğini okur.
     */
    protected function extractFromWord(string $path): string
    {
        $phpWord = IOFactory::load($path);
        $text = '';
        
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . " ";
                }
            }
        }
        
        return $text;
    }
}