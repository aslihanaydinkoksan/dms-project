<?php
/*/*
 * Bu job, bir belgenin içeriğini OCR (Optical Character Recognition) ile tarar ve
 * elde edilen metni veritabanına kaydeder. Tesseract OCR kütüphanesini kullanır.
 * 
 * Desteklenen dosya türleri: JPEG, PNG, PDF
 * 
 * Notlar:
 * - PDF dosyaları doğrudan Tesseract'a verilemez; önce Imagick ile sayfa sayfa görsellere çevrilir.
 * - OCR işlemi uzun sürebilir, bu nedenle timeout süresi 5 dakika olarak ayarlanmıştır.
 */
namespace App\Jobs;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Smalot\PdfParser\Parser as PdfParser;
use Exception;

class ProcessDocumentOcr implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    protected Document $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function handle(): void
    {
        $version = $this->document->currentVersion;

        if (!$version) return;

        $mimeType = $version->mime_type;
        $supportedMimes = ['image/jpeg', 'image/png', 'application/pdf'];

        if (!in_array($mimeType, $supportedMimes)) {
            return;
        }

        $physicalPath = Storage::disk('local')->path($version->file_path);
        
        if (!file_exists($physicalPath)) {
            Log::warning("OCR İşlemi Başarısız: Dosya diskte bulunamadı. Belge ID: {$this->document->id}");
            return;
        }

        try {
            $extractedText = '';

            if ($mimeType === 'application/pdf') {
                
                // ADIM 1: Önce İşletim Sistemi Bağımsız "Pure PHP" ile metni çıkarmayı dene
                $pdfParser = new PdfParser();
                $pdf = $pdfParser->parseFile($physicalPath);
                $extractedText = $pdf->getText();

                // ADIM 2: Eğer metin 50 karakterden kısaysa (muhtemelen taranmış/fotoğraf PDF'dir), OCR motorunu devreye sok
                if (strlen(trim($extractedText)) < 50) {
                    
                    // Ortam Farkındalığı (Environment Aware): Imagick kurulu mu? (Canlı sunucu vs Lokal PC)
                    if (class_exists('Imagick')) {
                        $extractedText = $this->extractTextFromScannedPdf($physicalPath);
                    } else {
                        Log::info("Bilgi: Taranmış PDF tespit edildi ancak lokalde 'Imagick' eklentisi bulunmadığı için OCR es geçildi. (Belge ID: {$this->document->id})");
                    }
                }

            } else {
                // JPG ve PNG dosyaları direkt Tesseract'a verilir
                $extractedText = (new TesseractOCR($physicalPath))
                    ->lang('tur', 'eng')
                    ->run();
            }

            // Metni temizle ve veritabanına yaz
            if (!empty(trim($extractedText))) {
                $cleanText = preg_replace('/\s+/', ' ', $extractedText);
                
                $this->document->updateQuietly([
                    'ocr_text' => trim($cleanText)
                ]);
            }

        } catch (Exception $e) {
            Log::error("Document Metin Çıkarma Hatası (Belge ID: {$this->document->id}): " . $e->getMessage());
        }
    }

    /**
     * Taranmış (Scanned) PDF'leri sayfa sayfa görsele çevirip OCR'dan geçirir.
     * SADECE Imagick kurulu olan ortamlarda (Canlı Sunucu) tetiklenir.
     */
    private function extractTextFromScannedPdf(string $pdfPath): string
    {
        $text = '';
        
        // Hata almamak için dinamik olarak sınıfı çağırıyoruz
        $imagickClass = '\Imagick';
        $imagick = new $imagickClass();
        
        $imagick->setResolution(300, 300);
        $imagick->readImage($pdfPath);

        $pages = $imagick->getNumberImages();

        for ($i = 0; $i < $pages; $i++) {
            $imagick->setIteratorIndex($i);
            $imagick->setImageFormat('tiff');
            
            $tmpPath = sys_get_temp_dir() . '/ocr_page_' . uniqid() . '.tiff';
            $imagick->writeImage($tmpPath);

            $pageText = (new TesseractOCR($tmpPath))
                ->lang('tur', 'eng')
                ->run();

            $text .= $pageText . ' ';

            @unlink($tmpPath);
        }

        $imagick->clear();
        
        return $text;
    }
}