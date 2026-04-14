<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;


class DocumentPermissionController extends Controller
{
    /**
     * Belgeye yeni bir istisna yetki ekler.
     */
    public function store(Request $request, Document $document)
    {
        // Yetki: Bu belgenin yetkilerini yönetmeye hakkı var mı? (Admin veya Owner)
        if (!Auth::user()->hasAnyRole(['Super Admin', 'Admin']) && ($document->currentVersion?->created_by !== Auth::id()) && !Auth::user()->can_manage_acl) {
            abort(403, 'Bu belgenin yetkilerini yönetme izniniz yok.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'access_level' => 'required|in:read,edit'
        ]);

        // syncWithoutDetaching, aynı kullanıcı varsa üzerine yazar (update), yoksa ekler
        $document->specificUsers()->syncWithoutDetaching([
            $request->user_id => ['access_level' => $request->access_level]
        ]);

        return back()->with('success', 'Kullanıcıya belge için özel yetki başarıyla tanımlandı.');
    }

    /**
     * Belgeden istisna yetkisini siler.
     */
    public function destroy(Document $document, User $user)
    {
        if (!Auth::user()->hasAnyRole(['Super Admin', 'Admin']) && ($document->currentVersion?->created_by !== Auth::id()) && !Auth::user()->can_manage_acl) {
            abort(403, 'Bu belgenin yetkilerini yönetme izniniz yok.');
        }

        $document->specificUsers()->detach($user->id);

        return back()->with('success', 'Özel yetki başarıyla kaldırıldı.');
    }
}
