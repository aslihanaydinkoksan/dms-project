<?php

namespace App\Services;

use Exception;
use Mpdf\Mpdf;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class SecurityWatermarkService
{
    /**
     * Dosyayı RAM üzerinde filigranlar ve Binary String olarak geri döner.
     */
    public function applyWatermark(string $physicalPath, string $mimeType, string $watermarkText): string
    {
        return match ($mimeType) {
            'application/pdf' => $this->watermarkPdf($physicalPath, $watermarkText),
            'image/jpeg', 'image/png' => $this->watermarkImage($physicalPath, $watermarkText),
            'text/html' => $this->watermarkHtml($physicalPath, $watermarkText),
            default => throw new Exception("Desteklenmeyen MIME türü: Filigran işlemi gerçekleştirilemez."),
        };
    }

    /**
     * PDF Motoru (mPDF & FPDI Altyapısı)
     */
    private function watermarkPdf(string $path, string $text): string
    {
        // tempDir ayarı mPDF'in çalışması için zorunludur, diskteki asıl dosyaya dokunulmaz
        $mpdf = new Mpdf(['tempDir' => storage_path('app/temp')]);

        // Orijinal dosyayı şablon (Source) olarak alıyoruz
        $pageCount = $mpdf->SetSourceFile($path);

        for ($i = 1; $i <= $pageCount; $i++) {
            $mpdf->AddPage();
            $templateId = $mpdf->ImportPage($i);
            $mpdf->UseTemplate($templateId);

            // Filigran Ayarları (Çapraz, %15 Opacity, UTF-8 Font)
            $mpdf->SetWatermarkText($text, 0.15);
            $mpdf->showWatermarkText = true;
            $mpdf->watermark_font = 'DejaVuSansCondensed';
        }

        // 'S' parametresi dosyayı kaydetmek yerine Binary String olarak döndürür
        return $mpdf->Output('', 'S');
    }

    /**
     * Image Motoru (Intervention Image v3)
     */
    private function watermarkImage(string $path, string $text): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($path);

        // Resim boyutuna göre dinamik bir font büyüklüğü hesaplıyoruz
        $fontSize = max(16, min($image->width() / 25, 64));

        $image->text($text, $image->width() / 2, $image->height() / 2, function ($font) use ($fontSize) {
            $font->size($fontSize);
            $font->color('rgba(255, 0, 0, 0.3)'); // %30 Saydam Kırmızı
            $font->align('center');
            $font->valign('middle');
            $font->angle(45); // Çapraz Yerleşim
        });

        return $image->encode()->toString();
    }

    /**
     * HTML Motoru (CSS Injection)
     */
    private function watermarkHtml(string $path, string $text): string
    {
        $content = file_get_contents($path);

        // Z-index: Max, Pointer-Events: None (Tıklamayı Engellemez), Kopyalanamaz (User-Select: None)
        $watermarkDiv = '
        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 2147483647; pointer-events: none; user-select: none; display: flex; align-items: center; justify-content: center; overflow: hidden;">
            <div style="transform: rotate(-45deg); font-size: 4vw; font-weight: bold; color: rgba(255, 0, 0, 0.15); white-space: nowrap; font-family: sans-serif;">
                ' . htmlspecialchars($text) . '
            </div>
        </div>';

        // Güvenli Enjeksiyon: </body> etiketinden hemen önceye bas, bulamazsa en sona ekle
        if (stripos($content, '</body>') !== false) {
            $content = str_ireplace('</body>', $watermarkDiv . '</body>', $content);
        } else {
            $content .= $watermarkDiv;
        }

        return $content;
    }
}
