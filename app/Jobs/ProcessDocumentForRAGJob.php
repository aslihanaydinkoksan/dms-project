<?php

namespace App\Jobs;

use Throwable; 
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\DocumentVersion;
use App\Models\DocumentEmbedding;
use App\Services\Document\TextExtractorService;
use App\Services\AI\TextChunkerService;
use App\Services\AI\RAGService;

/**
 * /
 * Doküman yüklendiğinde ve onaylandığında arka planda tetiklenecek bu kuyruk sınıfı; 
 * çıkarma, parçalama, vektörleştirme ve veritabanı kayıt işlemlerini senkron bloklamalardan kaçınarak orkestra eder.
 */
class ProcessDocumentForRAGJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; 

    public function __construct(
        public DocumentVersion $documentVersion
    ) {}

    public function handle(
        TextExtractorService $extractor,
        TextChunkerService $chunker,
        RAGService $ragService
    ): void {
        try {
            // Veritabanı ilişkilerinin null olma ihtimaline karşı Guard Clause (Koruyucu Blok)
            $document = $this->documentVersion->document;
            if (!$document) {
                throw new \Exception("Doküman ana kaydı (Document) bulunamadı. RAG iptal edildi.");
            }

            // DİKKAT: Veritabanındaki kolon adlarının 'file_path' ve 'mime_type' olduğundan emin ol.
            // Eğer kolon adların farklıysa (örn: path, type), null döneceği için TypeError alırsın.
            $filePath = (string) $this->documentVersion->file_path; 
            $mimeType = (string) $this->documentVersion->mime_type;

            if (empty($filePath)) {
                throw new \Exception("Doküman dosya yolu (file_path) boş. RAG iptal edildi.");
            }

            // 1. Text Extraction
            $extractedText = $extractor->extract($filePath, $mimeType);

            // Eğer PDF'den metin okunamadıysa (Örn: Sadece görsel içeren taranmış bir PDF ise) boşa işlem yapma
            if (empty(trim($extractedText))) {
                Log::warning("RAG Uyarı: Dokümandan metin çıkarılamadı (Boş içerik). ID: {$document->id}");
                return; 
            }

            // 2. Text Chunking
            $chunks = $chunker->chunk($extractedText);

            // 3. Vektörleştirme ve Kayıt
            foreach ($chunks as $index => $chunkText) {
                $vectorData = $ragService->generateEmbedding($chunkText);

                DocumentEmbedding::create([
                    'document_id' => $document->id,
                    'document_version_id' => $this->documentVersion->id,
                    'department_id' => $document->department_id ?? null,
                    'privacy_level' => $document->privacy_level,
                    'chunk_index' => $index,
                    'chunk_text' => $chunkText,
                    'external_vector_id' => null, 
                    'vector_data' => $vectorData,
                    'embedding_model' => config('ai.default.embedding'),
                    'token_count' => str_word_count($chunkText),
                ]);
            }

            Log::info("RAG Processing Completed for Document ID: {$document->id}");

        } catch (Throwable $e) { // <-- MİMARİ DOKUNUŞ: Exception yerine Throwable
            // İşlem sırasında bir hata oluşursa yakala ve sistemi durdurmadan fırlat
            throw new \Exception("RAG İşlemi Başarısız: " . $e->getMessage() . " | Satır: " . $e->getLine());
        }
    }

    /**
     * Hata durumunda tetiklenecek metod.
     * MİMARİ DOKUNUŞ: Exception yerine Throwable ile hem Error hem Exception sınıflarını yakalıyoruz.
     */
    public function failed(Throwable $exception): void 
    {
        Log::error("Doküman Vektörleştirme (RAG) işlemi başarısız oldu.", [
            'document_version_id' => $this->documentVersion->id,
            'error_message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);
    }
}