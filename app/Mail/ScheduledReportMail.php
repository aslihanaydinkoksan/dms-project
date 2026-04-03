<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScheduledReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $reportName,
        public string $filePath
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📊 Otomatik Sistem Raporu: ' . $this->reportName,
        );
    }

    public function content(): Content
    {
        // Basit bir metin içeriği (Blade oluşturmadan direkt HTML yazabiliriz)
        return new Content(
            htmlString: "<h3>KÖKSAN DYS Otomatik Raporlama Sistemi</h3><p>Talep etmiş olduğunuz <strong>{$this->reportName}</strong> başlıklı rapor ektedir.</p><p>İyi çalışmalar dileriz.</p>"
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->filePath)
                ->as($this->reportName . '.csv')
                ->withMime('text/csv'),
        ];
    }
}
