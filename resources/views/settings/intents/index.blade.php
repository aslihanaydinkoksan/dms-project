@extends('layouts.app')

@section('content')
    <div class="page-header flex-between"
        style="background: var(--surface-color); padding: 20px; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--card-shadow); margin-bottom: 25px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="background: #eff6ff; color: var(--accent-color); padding: 12px; border-radius: 12px;">
                <i data-lucide="brain-circuit" style="width: 28px; height: 28px;"></i>
            </div>
            <div>
                <h1 class="page-title" style="margin-bottom: 5px; font-size: 1.4rem; color: var(--primary-color);">
                    {{ __('Akıllı Asistan Eğitimi') }}</h1>
                <p class="text-muted" style="margin: 0; font-size: 0.9rem;">
                    {{ __('Asistanın hangi kelimelere nasıl tepki vereceğini buradan yönetebilirsiniz.') }}
                </p>
            </div>
        </div>
        <button type="button" class="btn btn-primary" onclick="openIntentForm()" style="display: flex; gap: 8px;">
            <i data-lucide="plus" style="width: 18px;"></i> {{ __('Yeni Yetenek Öğret') }}
        </button>
    </div>

    @include('partials.alerts')

    <div style="display: grid; grid-template-columns: 1fr 350px; gap: 20px; align-items: start;">

        {{-- SOL TARAF: MEVCUT YETENEKLER LİSTESİ --}}
        <div class="card glass-card"
            style="background: var(--surface-color); border-radius: var(--border-radius); border: 1px solid var(--border-color); padding: 20px;">
            <h3
                style="font-size: 1.1rem; color: var(--primary-color); margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                {{ __('Asistanın Mevcut Bilgi Dağarcığı') }}
            </h3>

            @forelse($intents as $intent)
                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: flex-start; transition: all 0.2s;"
                    class="intent-card hover-shadow">
                    <div>
                        <h4
                            style="margin: 0 0 10px 0; color: var(--text-color); font-size: 1rem; display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="message-square" style="width: 16px; color: var(--accent-color);"></i>
                            {{ $intent->intent_name }}
                        </h4>

                        <div style="margin-bottom: 10px; display: flex; flex-wrap: wrap; gap: 5px;">
                            <span
                                style="font-size: 0.75rem; font-weight: bold; color: var(--text-muted); margin-right: 5px;">{{ __('Tetikleyiciler:') }}</span>
                            @foreach ($intent->keywords as $keyword)
                                <span
                                    style="background: #e0f2fe; color: #0284c7; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; border: 1px solid #bae6fd;">
                                    {{ $keyword }}
                                </span>
                            @endforeach
                        </div>

                        <div
                            style="background: white; padding: 10px; border-radius: 6px; border-left: 3px solid var(--success-color); font-size: 0.85rem; color: var(--text-muted); font-style: italic;">
                            "{{ Str::limit($intent->response_text, 100) }}"
                        </div>

                        @if ($intent->action_route)
                            <div style="margin-top: 10px; font-size: 0.8rem; color: var(--accent-color); font-weight: 500;">
                                <i data-lucide="link" style="width: 12px; vertical-align: middle;"></i>
                                Yönlendirme: {{ $intent->action_button_text }} (Rotası: {{ $intent->action_route }})
                            </div>
                        @endif
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button type="button" class="btn btn-sm btn-outline-primary"
                            onclick="editIntent({{ $intent->toJson() }})" title="Düzenle" style="padding: 6px 10px;">
                            <i data-lucide="edit-2" style="width: 14px;"></i>
                        </button>
                        <form action="{{ route('settings.intents.destroy', $intent->id) }}" method="POST"
                            onsubmit="return confirm('Bu yeteneği asistanın hafızasından silmek istediğinize emin misiniz?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Sil"
                                style="padding: 6px 10px;">
                                <i data-lucide="trash-2" style="width: 14px;"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div
                    style="text-align: center; padding: 40px 20px; color: var(--text-muted); background: #f8fafc; border-radius: 8px; border: 1px dashed var(--border-color);">
                    <i data-lucide="brain" style="width: 48px; height: 48px; opacity: 0.2; margin-bottom: 15px;"></i>
                    <p style="margin: 0;">Asistanın hafızası şu an tamamen boş.</p>
                    <p style="font-size: 0.85rem; margin-top: 5px;">Ona yeni kelimeler ve cevaplar öğreterek akıllanmasını
                        sağlayın.</p>
                </div>
            @endforelse
        </div>

        {{-- SAĞ TARAF: EKLEME/DÜZENLEME FORMU --}}
        <div class="card glass-card"
            style="background: var(--surface-color); border-radius: var(--border-radius); border: 1px solid var(--border-color); position: sticky; top: 20px;">
            <div style="padding: 20px; border-bottom: 1px solid var(--border-color); background: #f8fafc;">
                <h3 id="formTitle"
                    style="font-size: 1.1rem; color: var(--primary-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="graduation-cap" style="color: var(--accent-color);"></i>
                    {{ __('Asistanı Eğit') }}
                </h3>
            </div>

            <form action="{{ route('settings.intents.store') }}" method="POST" style="padding: 20px;">
                @csrf
                <input type="hidden" name="intent_id" id="intent_id" value="">

                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">Konu / Amaç
                        Başlığı <span class="text-danger">*</span></label>
                    <input type="text" name="intent_name" id="intent_name" class="form-control" required
                        placeholder="Örn: Belge Yükleme Talebi"
                        style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">
                        Tetikleyici Kelimeler <span class="text-danger">*</span>
                        <i data-lucide="help-circle" style="width: 12px; color: #94a3b8; cursor: help;"
                            title="Kullanıcı bu kelimelerden birini yazarsa bot cevap verir. Virgülle ayırın."></i>
                    </label>
                    <textarea name="keywords" id="keywords" class="form-control" required rows="2"
                        placeholder="Örn: evrak ekle, yeni belge, dosya yükle"
                        style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-family: inherit; font-size: 0.85rem; resize: vertical;"></textarea>
                    <small style="color: var(--text-muted); font-size: 0.75rem;">Kelimeleri virgül (,) ile ayırarak
                        yazın.</small>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">Asistanın
                        Cevabı <span class="text-danger">*</span></label>
                    <textarea name="response_text" id="response_text" class="form-control" required rows="4"
                        placeholder="Örn: Yeni bir belge yüklemek için aşağıdaki butonu kullanabilirsiniz."
                        style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-family: inherit; font-size: 0.85rem; resize: vertical;"></textarea>
                </div>

                <div
                    style="padding: 15px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; margin-bottom: 20px;">
                    <label
                        style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 10px; color: var(--secondary-color);">
                        <i data-lucide="link" style="width: 14px; vertical-align: middle;"></i> Aksiyon Butonu (İsteğe
                        Bağlı)
                    </label>

                    <div style="margin-bottom: 10px;">
                        <input type="text" name="action_button_text" id="action_button_text" class="form-control"
                            placeholder="Buton Yazısı (Örn: Belge Yükle)"
                            style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px; font-size: 0.85rem;">
                    </div>
                    <div>
                        <input type="text" name="action_route" id="action_route" class="form-control"
                            placeholder="Rota Adı (Örn: documents.create)"
                            style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 4px; font-size: 0.85rem; font-family: monospace;">
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success"
                        style="flex: 1; padding: 10px; justify-content: center;">
                        <i data-lucide="save" style="width: 16px;"></i> <span
                            id="submitBtnText">{{ __('Kaydet') }}</span>
                    </button>
                    <button type="button" id="cancelEditBtn" class="btn btn-outline-secondary"
                        onclick="openIntentForm()" style="display: none; padding: 10px;">
                        <i data-lucide="x" style="width: 16px;"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .hover-shadow:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border-color: #cbd5e1 !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });

        // Formu Temizle ve Yeni Ekleme Moduna Geç
        function openIntentForm() {
            document.getElementById('intent_id').value = '';
            document.getElementById('intent_name').value = '';
            document.getElementById('keywords').value = '';
            document.getElementById('response_text').value = '';
            document.getElementById('action_button_text').value = '';
            document.getElementById('action_route').value = '';

            document.getElementById('formTitle').innerHTML =
                '<i data-lucide="graduation-cap" style="color: var(--accent-color);"></i> Asistanı Eğit';
            document.getElementById('submitBtnText').innerText = 'Kaydet';
            document.getElementById('cancelEditBtn').style.display = 'none';

            lucide.createIcons();
            document.getElementById('intent_name').focus();
        }

        // Düzenleme Moduna Geç ve Verileri Doldur
        function editIntent(intentData) {
            document.getElementById('intent_id').value = intentData.id;
            document.getElementById('intent_name').value = intentData.intent_name;
            // JSON array'i virgüllü stringe çevir
            document.getElementById('keywords').value = Array.isArray(intentData.keywords) ? intentData.keywords.join(
                ', ') : intentData.keywords;
            document.getElementById('response_text').value = intentData.response_text;
            document.getElementById('action_button_text').value = intentData.action_button_text || '';
            document.getElementById('action_route').value = intentData.action_route || '';

            document.getElementById('formTitle').innerHTML =
                '<i data-lucide="edit" style="color: var(--warning-color);"></i> Hafızayı Düzenle';
            document.getElementById('submitBtnText').innerText = 'Güncelle';
            document.getElementById('cancelEditBtn').style.display = 'block';

            lucide.createIcons();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    </script>
@endpush
