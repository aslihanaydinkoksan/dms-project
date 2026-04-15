<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\FavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    protected FavoriteService $favoriteService;

    public function __construct(FavoriteService $favoriteService)
    {
        $this->favoriteService = $favoriteService;
    }

    public function toggle(Document $document): JsonResponse
    {
        // 1. IDE hatasını önlemek için doğrudan Gate Facade kullanıyoruz
        Gate::authorize('view', $document); 

        // 2. IDE'ye auth()->user() nesnesinin bizim User modelimiz olduğunu söylüyoruz
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $result = $this->favoriteService->toggleFavorite($user, $document);

        return response()->json($result);
    }
    public function sidebar(\Illuminate\Http\Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Arama kelimesini yakala
        $keyword = $request->input('fav_search');

        // Sadece favorileri çek, arama yap ve yetki (Policy) filtresinden geçir
        $favoriteDocuments = $user->favorites()
            ->with(['documentType', 'currentVersion'])
            ->searchInFavorites($keyword) // YENİ: Arama filtresi eklendi
            ->latest('document_user_favorites.created_at')
            ->get()
            ->filter(function ($document) use ($user) {
                return $user->can('view', $document);
            });

        // Keyword'ü de gönderiyoruz ki partial view içinde 'Arama sonucu bulunamadı' mesajı doğru çalışsın
        return view('dashboard.partials.favorites-list', compact('favoriteDocuments', 'keyword'))->render();
    }
    public function updateNote(\Illuminate\Http\Request $request, Document $document): JsonResponse
    {
        Gate::authorize('view', $document);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Sadece bu kullanıcıya ait pivot tablodaki notu günceller
        $user->favorites()->updateExistingPivot($document->id, [
            'note' => $request->input('note')
        ]);

        return response()->json([
            'success' => true,
            'note' => $request->input('note'),
            'message' => 'Not güncellendi.'
        ]);
    }
}
