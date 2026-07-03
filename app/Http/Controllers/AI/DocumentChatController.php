<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentChatRequest;
use App\Models\Document;
use App\Services\AI\SemanticSearchService;
use App\Services\AI\RAGService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Exception;
use Illuminate\Support\Facades\Log;

class DocumentChatController extends Controller
{
    public function __construct(
        protected SemanticSearchService $semanticSearch,
        protected RAGService $ragService
    ) {}

    /**
     * Doküman üzerinden yapay zeka ile sohbet endpoint'i.
     */
    public function chat(DocumentChatRequest $request): JsonResponse
    {
        try {
            $document = Document::findOrFail($request->validated('document_id'));
            $user = $request->user();

            // RBAC Zırhı: Kullanıcının bu spesifik dokümanı görme yetkisi var mı?
            // (DocumentPolicy içerisinde view metodu tanımlanmış olmalıdır)
            Gate::authorize('view', $document);

            $question = $request->validated('message');

            // 1. Semantik arama ile soruya uygun metin parçalarını bul
            $contextChunks = $this->semanticSearch->search($question, $document->id, $user);

            if (empty($contextChunks)) {
                return response()->json([
                    'status' => 'success',
                    'answer' => 'Bu doküman henüz yapay zeka analizinden geçirilmemiş veya aradığınız bilgiye dair bir bağlam bulunamadı.'
                ]);
            }

            // 2. Bağlamı ve soruyu LLM'e vererek nihai cevabı üret
            $answer = $this->ragService->answerQuery($question, $contextChunks);

            // Audit Logging (İzlenebilirlik): Hangi kullanıcının hangi dokümana ne sorduğunu logla
            Log::info('RAG Sohbet İşlemi', [
                'user_id' => $user->id,
                'document_id' => $document->id,
                'question' => $question
            ]);

            return response()->json([
                'status' => 'success',
                'answer' => $answer
            ]);
        } catch (Exception $e) {
            Log::error("Yapay Zeka Sohbet Hatası: " . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Sohbet sırasında sistemsel bir hata oluştu. Lütfen tekrar deneyin.'
            ], 500);
        }
    }
}
