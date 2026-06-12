@extends('layouts.app')

@section('content')
    <div class="page-header mb-20">
        <a href="{{ route('process-templates.index') }}" class="btn btn-sm btn-outline-secondary mb-10"
            style="display: inline-flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" style="width: 14px;"></i> {{ __('Geri Dön') }}
        </a>
        <h1 class="page-title" style="font-size: 1.8rem; color: var(--primary-color);">✨
            {{ __('Yeni Süreç Şablonu Tasarla') }}</h1>
    </div>

    @include('partials.alerts')

    <form action="{{ route('process-templates.store') }}" method="POST" id="processTemplateForm">
        @csrf
        <div class="card glass-card mb-30"
            style="border-radius: 12px; padding: 25px; border-top: 4px solid var(--accent-color);">
            <h4 style="margin: 0 0 20px 0; color: var(--secondary-color); display:flex; align-items:center; gap:8px;">
                <i data-lucide="settings" style="width: 20px; color: var(--accent-color);"></i>
                {{ __('Süreç Temel Ayarları') }}
            </h4>

            <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">{{ __('Süreç Adı') }} <span
                            class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="Örn: Masraf Onay Süreci" required
                        style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color);">
                </div>

                <div class="form-group">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">{{ __('Yetkili Departman') }} <span
                            class="text-danger">*</span></label>
                    <select name="department_id" class="form-control" required
                        style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color);">
                        <option value="">{{ __('-- Departman Seçin --') }}</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            {{-- ZORUNLU ÇEKİRDEK EKİP (ANTI-BYPASS) --}}
            <div class="form-group mb-20">
                <label style="font-weight: 600; margin-bottom: 8px; display: block; color: var(--danger-color);">
                    <i data-lucide="shield-alert" style="width: 16px; vertical-align: middle;"></i>
                    {{ __('Zorunlu Çekirdek Ekip (Opsiyonel)') }}
                </label>
                <p class="text-muted" style="font-size: 0.8rem; margin-top: -5px; margin-bottom: 10px;">
                    Bu süreci başlatan kullanıcılar, aşağıda seçtiğiniz gruptaki kişileri projeye atamak
                    <strong>zorundadır.</strong> Kullanıcı eklemese bile sistem bu kişileri otomatik olarak sürece dahil
                    eder.
                </p>
                <select name="mandatory_user_group_id" class="form-control"
                    style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color);">
                    <option value="">{{ __('-- Zorunlu Grup Yok (Sadece Ad-Hoc Ekip) --') }}</option>
                    @foreach ($userGroups as $group)
                        <option value="{{ $group->id }}"
                            {{ isset($processTemplate) && $processTemplate->mandatory_user_group_id == $group->id ? 'selected' : '' }}>
                            🛡️ {{ $group->name }}
                        </option>
                    @endforeach
                </select>
                {{-- SIKIMOD / ESNEK KADRO İZNİ (ANTI-BYPASS GÜVENLİK) --}}
                <div class="form-group mb-20" style="margin-top: 15px;">
                    <input type="hidden" name="allow_ad_hoc_members" value="0"> {{-- Checkbox seçilmezse 0 gitmesi için --}}
                    <label
                        style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; color: var(--text-color);">
                        <input type="checkbox" name="allow_ad_hoc_members" value="1"
                            {{ !isset($processTemplate) || $processTemplate->allow_ad_hoc_members ? 'checked' : '' }}
                            style="width: 18px; height: 18px; accent-color: var(--primary-color);">
                        {{ __('Esnek Kadro İzni (Bu süreç başlatılırken dışarıdan ekstra personel davet edilebilsin)') }}
                    </label>
                    <p class="text-muted" style="font-size: 0.8rem; margin-left: 26px; margin-top: 2px;">
                        Eğer bu izni **kapatırsanız** süreç "Sıkı Mod" ile korunur; süreç başlatılırken kullanıcılar ekstra
                        kimseyi projeye dahil edemez, sadece yukarıda seçtiğiniz zorunlu grup süreci yönetir.
                    </p>
                </div>
            </div>
            <div class="form-group mt-20" style="margin-top: 20px;">
                <label
                    style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; color: var(--text-color);">
                    <input type="checkbox" name="requires_document_on_closure" value="1"
                        style="width: 18px; height: 18px; accent-color: var(--primary-color);">
                    {{ __('Süreç Kapatılırken Fiziksel/Dijital Belge Yüklenmesi Zorunlu Olsun (Kanıt)') }}
                </label>
            </div>
        </div>
        {{-- DİNAMİK FORM BUILDER (EAV/JSON) --}}
        <div class="card glass-card" style="border-radius: 12px; padding: 25px; border-top: 4px solid #10b981;">
            <div class="flex-between mb-20"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h4 style="margin: 0; color: var(--secondary-color); display:flex; align-items:center; gap:8px;">
                    <i data-lucide="layout-template" style="width: 20px; color: #10b981;"></i>
                    {{ __('Dinamik Form Alanları (No-Code)') }}
                </h4>
                <button type="button" id="addFieldBtn" class="btn btn-sm btn-outline-success"
                    style="display: flex; align-items: center; gap: 6px;">
                    <i data-lucide="plus" style="width: 16px;"></i> {{ __('Yeni Alan Ekle') }}
                </button>
            </div>

            <div class="alert alert-info"
                style="font-size: 0.85rem; padding: 12px; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; border-radius: 8px; margin-bottom: 20px;">
                <i data-lucide="info" style="width: 16px; vertical-align: text-bottom; margin-right: 5px;"></i>
                {{ __('Kullanıcıların bu süreci başlatırken doldurmasını istediğiniz özel alanları aşağıya ekleyin. Sistem veritabanı sütunlarına ihtiyaç duymadan bunları yönetecek.') }}
            </div>

            <div id="formBuilderContainer" style="display: flex; flex-direction: column; gap: 15px;">
            </div>

            <div class="mt-30 text-right"
                style="margin-top: 30px; text-align: right; border-top: 1px solid var(--border-color); padding-top: 20px;">
                <button type="submit" class="btn btn-primary"
                    style="padding: 12px 30px; font-weight: bold; font-size: 1.1rem; display: inline-flex; align-items: center; gap: 8px;">
                    <i data-lucide="save"></i> {{ __('Şablonu Kaydet') }}
                </button>
            </div>
        </div>
    </form>

    {{-- TEMPLATE HTML FOR JS --}}
    <template id="field-row-template">
        <div class="field-row"
            style="display: grid; grid-template-columns: 2fr 2fr 1.5fr auto auto; gap: 15px; align-items: center; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px dashed #cbd5e1; transition: all 0.2s;">
            <div>
                <label style="font-size: 0.8rem; font-weight: 600; margin-bottom: 4px; display: block;">Görünen Ad (Label)
                    *</label>
                <input type="text" name="fields[__INDEX__][label]" class="form-control label-input"
                    placeholder="Örn: Araç Plakası" required style="width: 100%; border-radius: 6px;">
            </div>
            <div>
                <label style="font-size: 0.8rem; font-weight: 600; margin-bottom: 4px; display: block;">Sistem Kodu (Key)
                    *</label>
                <input type="text" name="fields[__INDEX__][name]" class="form-control key-input"
                    placeholder="arac_plakasi" required style="width: 100%; border-radius: 6px; font-family: monospace;">
            </div>
            <div>
                <label style="font-size: 0.8rem; font-weight: 600; margin-bottom: 4px; display: block;">Veri Tipi</label>
                <select name="fields[__INDEX__][type]" class="form-control type-select" style="width: 100%; border-radius: 6px;">
                    <option value="text">Kısa Metin (Text)</option>
                    <option value="number">Sayısal (Number)</option>
                    <option value="date">Tarih (Date)</option>
                    <option value="textarea">Uzun Metin (Textarea)</option>
                    <option value="select">Açılır Menü (Dropdown)</option>
                </select>
            </div>
            <div class="options-wrapper" style="display: none; grid-column: span 2;">
                <label style="font-size: 0.8rem; font-weight: 600; color: var(--accent-color);">Menü Seçenekleri (Virgülle
                    ayırın) *</label>
                <input type="text" name="fields[__INDEX__][options_raw]" class="form-control options-input"
                    placeholder="Örn: Onaylandı, Reddedildi, Beklemede">
            </div>
            <div style="padding-top: 20px; text-align: center;">
                <label
                    style="display: flex; align-items: center; gap: 5px; font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" name="fields[__INDEX__][required]" value="1"
                        style="width: 16px; height: 16px; accent-color: var(--primary-color);"> Zorunlu
                </label>
            </div>
            <div style="padding-top: 20px;">
                <button type="button" class="btn btn-outline-danger remove-field-btn" style="padding: 6px 12px;"><i
                        data-lucide="trash-2" style="width: 16px;"></i></button>
            </div>
        </div>
    </template>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();

            const container = document.getElementById('formBuilderContainer');
            const template = document.getElementById('field-row-template').innerHTML;
            let fieldIndex = 0;

            document.getElementById('addFieldBtn').addEventListener('click', function() {
                // Şablonu al ve index'i değiştir
                const html = template.replace(/__INDEX__/g, fieldIndex);
                container.insertAdjacentHTML('beforeend', html);

                // Yeni eklenen satırı yakala
                const newRow = container.lastElementChild;

                // Satır bazlı elementleri yakala (Hata giderildi: const yerine let veya scoping ile)
                const typeSelect = newRow.querySelector('.type-select');
                const optionsWrapper = newRow.querySelector('.options-wrapper');
                const optionsInput = newRow.querySelector('.options-input');
                const labelInput = newRow.querySelector('.label-input');
                const keyInput = newRow.querySelector('.key-input');

                // 1. Dropdown (Select) tipi seçildiğinde seçenek kutusunu göster
                typeSelect.addEventListener('change', function() {
                    if (this.value === 'select') {
                        optionsWrapper.style.display = 'block';
                        optionsInput.required = true;
                    } else {
                        optionsWrapper.style.display = 'none';
                        optionsInput.required = false;
                        optionsInput.value = '';
                    }
                });

                // 2. Label'dan Key'e otomatik Slug çevirici (Boşalan özellik geri geldi)
                labelInput.addEventListener('keyup', function() {
                    if (!keyInput.value || keyInput.dataset.auto === 'true') {
                        keyInput.dataset.auto = 'true';
                        keyInput.value = this.value.toLowerCase()
                            .replace(/ğ/g, 'g').replace(/ü/g, 'u').replace(/ş/g, 's')
                            .replace(/ı/g, 'i').replace(/ö/g, 'o').replace(/ç/g, 'c')
                            .replace(/[^a-z0-9]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
                    }
                });

                keyInput.addEventListener('input', () => keyInput.dataset.auto = 'false');

                lucide.createIcons();
                fieldIndex++;
            });

            container.addEventListener('click', function(e) {
                const btn = e.target.closest('.remove-field-btn');
                if (btn) {
                    btn.closest('.field-row').remove();
                }
            });
        });
    </script>
@endpush
