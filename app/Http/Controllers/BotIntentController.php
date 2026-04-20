<?php

namespace App\Http\Controllers;

use App\Models\BotIntent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BotIntentController extends Controller
{
    /**
     * Zeka yöneticisi listesini göster
     */
    public function index()
    {
        // Sadece Admin ve Super Admin görebilir (Route middleware ile de korunacak)
        if (!Auth::user()->hasAnyRole(['Super Admin', 'Admin'])) {
            abort(403, 'Asistan zekasını yönetme yetkiniz yok.');
        }

        $intents = BotIntent::latest()->get();

        return view('settings.intents.index', compact('intents'));
    }

    /**
     * Yeni zeka ekleme/düzenleme formundan gelen veriyi kaydet (Upsert)
     */
    public function store(Request $request)
    {
        if (!Auth::user()->hasAnyRole(['Super Admin', 'Admin'])) {
            abort(403);
        }

        $request->validate([
            'intent_name' => 'required|string|max:255',
            'keywords' => 'required|string', // Virgülle ayrılmış string gelecek, biz diziye çevireceğiz
            'response_text' => 'required|string|max:1000',
            'action_route' => 'nullable|string|max:255',
            'action_button_text' => 'nullable|string|max:255'
        ]);

        // Virgülle ayrılmış kelimeleri temiz bir diziye (array) dönüştür
        $keywordsArray = array_map('trim', explode(',', $request->keywords));
        // Boş olanları filtrele
        $keywordsArray = array_filter($keywordsArray);

        // Eğer ID geldiyse Güncelle, gelmediyse Yeni Oluştur
        if ($request->has('intent_id') && !empty($request->intent_id)) {
            $intent = BotIntent::findOrFail($request->intent_id);
            $message = 'Asistan zekası güncellendi!';
        } else {
            $intent = new BotIntent();
            $message = 'Asistana yeni bir yetenek öğretildi!';
        }

        $intent->intent_name = $request->intent_name;
        $intent->keywords = $keywordsArray;
        $intent->response_text = $request->response_text;
        $intent->action_route = $request->action_route;
        $intent->action_button_text = $request->action_button_text;

        $intent->save();

        return back()->with('success', $message);
    }

    /**
     * Bir zekayı sil
     */
    public function destroy(BotIntent $intent)
    {
        if (!Auth::user()->hasAnyRole(['Super Admin', 'Admin'])) {
            abort(403);
        }

        $intent->delete();

        return back()->with('success', 'Asistanın bu yeteneği silindi.');
    }
}
