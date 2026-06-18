<?php

namespace App\Services;

use Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfGeneratorService
{
    /**
     * Form verilerinden kurumsal bir PDF dökümanı üretir ve binary string döner.
     * * @param string $title Belge Başlığı
     * @param array $metadata Kullanıcının doldurduğu dinamik veriler
     * @param array $fieldSpecifications Şablondaki alan tanımları (Label eşleşmesi için)
     * @return string Raw PDF Binary Data
     * @throws Exception
     */
    public function generateHtmlDocument(string $title, array $metadata, array $fieldSpecifications): string
    {
        // Label haritasını çıkar (Örn: 'vardiya' => 'Çalışma Vardiyası')
        $labels = [];
        foreach ($fieldSpecifications as $spec) {
            if (isset($spec['name'], $spec['label'])) {
                $labels[$spec['name']] = $spec['label'];
            }
        }

        // HTML İçerik Gövdesini İnşa Et
        $htmlRows = '';
        foreach ($metadata as $key => $value) {
            $displayLabel = $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
            $displayValue = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : htmlspecialchars((string)$value);
            
            $htmlRows .= "
                <tr>
                    <td class='label-cell'>{$displayLabel}</td>
                    <td class='value-cell'>{$displayValue}</td>
                </tr>";
        }

        // Kurumsal HTML Şablonu (Sıfır Esneklik Kırılması - Tam Sabit Yapı)
        $htmlTemplate = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                @page {
                    size: A4;
                    margin: 0; /* Kenar boşluklarını tamamen elle yöneteceğiz */
                }
                body {
                    font-family: 'Arial', 'Helvetica', sans-serif;
                    color: #2b2b2b;
                    line-height: 1.4;
                    margin: 0;
                    padding: 0;
                }
                /* KRİTİK GÜVENLİK ALANI: ISO Antetinin altına sıfır çakışmayla oturması için üst boşluk */
                .page-container {
                    padding-top: 38mm; 
                    padding-left: 20mm;
                    padding-right: 20mm;
                    padding-bottom: 20mm;
                    box-sizing: border-box;
                }
                .document-title {
                    font-size: 16pt;
                    font-weight: bold;
                    color: #0d2c54;
                    text-align: center;
                    margin-bottom: 25px;
                    text-transform: uppercase;
                    border-bottom: 2px solid #0d2c54;
                    padding-bottom: 10px;
                }
                .meta-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 15px;
                }
                .meta-table th, .meta-table td {
                    border: 1px solid #d3d3d3;
                    padding: 10px 12px;
                    font-size: 10pt;
                    vertical-align: top;
                }
                .label-cell {
                    background-color: #f7f9fa;
                    font-weight: bold;
                    width: 35%;
                    color: #4a5568;
                }
                .value-cell {
                    width: 65%;
                    color: #1a202c;
                }
                .footer-notice {
                    margin-top: 40px;
                    font-size: 8pt;
                    color: #718096;
                    text-align: center;
                    font-style: italic;
                }
            </style>
        </head>
        <body>
            <div class='page-container'>
                <div class='document-title'>{$title}</div>
                <table class='meta-table'>
                    <tbody>
                        {$htmlRows}
                    </tbody>
                </table>
                <div class='footer-notice'>
                    Bu belge KÖKSAN DMS Akıllı Form Altyapısı tarafından otomatik olarak üretilmiştir.
                </div>
            </div>
        </body>
        </html>";

        // DomPDF Adaptasyonu ile Render İşlemi
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false); // Güvenlik sebebiyle dış kaynak çekimi kapalı
        $options->setDefaultFont('Arial');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($htmlTemplate);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output() ?? throw new Exception("PDF dökümanı generate edilirken binary çıktı alınamadı.");
    }
}