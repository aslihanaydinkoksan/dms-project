@extends('layouts.app')

@section('content')
    <div class="page-header mb-20">
        <a href="{{ route('process-templates.index') }}" class="btn btn-sm btn-outline-secondary mb-10"
            style="display: inline-flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" style="width: 14px;"></i> {{ __('Geri Dön') }}
        </a>
        <h1 class="page-title" style="font-size: 1.8rem; color: var(--primary-color);">✏️ {{ __('Şablonu Düzenle:') }}
            {{ $processTemplate->name }}</h1>
    </div>

    @include('partials.alerts')

    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 25px; align-items: start;">

        {{-- SOL TARAF: ŞABLON VE DİNAMİK FORM AYARLARI --}}
        <div>
            <form action="{{ route('process-templates.update', $processTemplate->id) }}" method="POST">
                @csrf @method('PUT')

                <div class="card glass-card mb-25"
                    style="border-radius: 12px; padding: 25px; border-top: 4px solid var(--accent-color);">
                    <h4 style="margin: 0 0 20px 0; color: var(--secondary-color);"><i data-lucide="settings"
                            style="width: 20px; vertical-align: middle;"></i> {{ __('Süreç Temel Ayarları') }}</h4>
                    <div class="form-group mb-15">
                        <label style="font-weight: 600; margin-bottom: 8px; display: block;">{{ __('Süreç Adı') }}</label>
                        <input type="text" name="name" class="form-control" value="{{ $processTemplate->name }}"
                            required style="width: 100%; padding: 10px; border-radius: 6px;">
                    </div>
                    <div class="form-group mb-15">
                        <label
                            style="font-weight: 600; margin-bottom: 8px; display: block;">{{ __('Yetkili Departman') }}</label>
                        <select name="department_id" class="form-control" required
                            style="width: 100%; padding: 10px; border-radius: 6px;">
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}"
                                    {{ $processTemplate->department_id == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- ZORUNLU ÇEKİRDEK EKİP (ANTI-BYPASS) - EKLENEN KISIM --}}
                    <div class="form-group mt-20"
                        style="margin-top: 25px; border-top: 1px dashed var(--border-color); padding-top: 20px; margin-bottom: 20px;">
                        <label style="font-weight: 600; margin-bottom: 8px; display: block; color: var(--danger-color);">
                            <i data-lucide="shield-alert" style="width: 16px; vertical-align: middle;"></i>
                            {{ __('Zorunlu Çekirdek Ekip (Opsiyonel)') }}
                        </label>
                        <p class="text-muted" style="font-size: 0.8rem; margin-top: -5px; margin-bottom: 10px;">
                            Bu süreci başlatan kullanıcılar, aşağıda seçtiğiniz gruptaki kişileri projeye atamak
                            <strong>zorundadır.</strong> Kullanıcı eklemese bile sistem bu kişileri otomatik olarak sürece
                            dahil eder.
                        </p>
                        <select name="mandatory_user_group_id" class="form-control"
                            style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color);">
                            <option value="">{{ __('-- Zorunlu Grup Yok (Sadece Ad-Hoc Ekip) --') }}</option>
                            @foreach ($userGroups as $group)
                                <option value="{{ $group->id }}"
                                    {{ $processTemplate->mandatory_user_group_id == $group->id ? 'selected' : '' }}>
                                    🛡️ {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600;">
                            <input type="checkbox" name="requires_document_on_closure" value="1"
                                {{ $processTemplate->requires_document_on_closure ? 'checked' : '' }}
                                style="width: 18px; height: 18px; accent-color: var(--primary-color);">
                            {{ __('Kapanışta Belge Zorunlu') }}
                        </label>
                    </div>
                </div>

                <div class="card glass-card" style="border-radius: 12px; padding: 25px; border-top: 4px solid #10b981;">
                    <div class="flex-between mb-20"
                        style="display: flex; justify-content: space-between; align-items: center;">
                        <h4 style="margin: 0; color: var(--secondary-color);"><i data-lucide="layout-template"
                                style="width: 20px; vertical-align: middle;"></i> {{ __('Dinamik Form Alanları') }}</h4>
                        <button type="button" id="addFieldBtn" class="btn btn-sm btn-outline-success"><i data-lucide="plus"
                                style="width: 16px; vertical-align: middle;"></i> Yeni Alan</button>
                    </div>

                    <div id="formBuilderContainer" style="display: flex; flex-direction: column; gap: 15px;">
                    </div>

                    <div class="mt-20 text-right">
                        <button type="submit" class="btn btn-primary" style="padding: 10px 25px;"><i data-lucide="save"
                                style="width: 16px; vertical-align: middle;"></i> Güncelle</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- SAĞ TARAF: KANBAN AŞAMALARI (STAGES) --}}
        <div class="card glass-card"
            style="border-radius: 12px; padding: 25px; border-top: 4px solid #f59e0b; position: sticky; top: 20px;">
            <h4 style="margin: 0 0 10px 0; color: var(--secondary-color);"><i data-lucide="kanban"
                    style="width: 20px; vertical-align: middle; color: #f59e0b;"></i> {{ __('Süreç Kanban Aşamaları') }}
            </h4>
            <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 20px;">Görevlerin süreç boyunca geçeceği
                sütunları (örn: Bekliyor, İnceleniyor) oluşturun.</p>

            {{-- Aşama Ekleme Formu --}}
            <form action="{{ route('process-stages.store', $processTemplate->id) }}" method="POST"
                style="display: flex; gap: 10px; margin-bottom: 20px; background: #fffbeb; padding: 15px; border-radius: 8px; border: 1px solid #fde68a;">
                @csrf
                <input type="text" name="name" class="form-control" placeholder="Aşama Adı" required
                    style="flex: 2; padding: 8px; border-radius: 4px;">
                <input type="color" name="color" value="#3b82f6" title="Sütun Rengi"
                    style="flex: 0.5; height: 38px; border: none; cursor: pointer; background: transparent;">
                <button type="submit" class="btn btn-warning"
                    style="flex: 1; font-weight: bold; color: #92400e;">Ekle</button>
            </form>

            {{-- Aşama Listesi --}}
            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">
                @forelse($processTemplate->stages as $stage)
                    <li
                        style="display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; border-left: 4px solid {{ $stage->color ?? '#cbd5e1' }};">
                        <span style="font-weight: 600; color: var(--text-color);">{{ $stage->name }}</span>
                        <form action="{{ route('process-stages.destroy', $stage->id) }}" method="POST"
                            onsubmit="return confirm('Sütunu silmek istediğinize emin misiniz?');" style="margin: 0;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" style="padding: 4px 8px;"><i
                                    data-lucide="trash-2" style="width: 14px;"></i></button>
                        </form>
                    </li>
                @empty
                    <li class="text-center text-muted"
                        style="padding: 20px; border: 1px dashed #cbd5e1; border-radius: 8px;">
                        Hiç aşama eklenmemiş. Lütfen yukarıdan süreç adımlarını tanımlayın.
                    </li>
                @endforelse
            </ul>
        </div>

    </div>

    {{-- TEMPLATE HTML FOR JS --}}
    <template id="field-row-template">
        <div class="field-row"
            style="display: grid; grid-template-columns: 2fr 2fr 1.5fr auto auto; gap: 10px; align-items: center; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px dashed #cbd5e1;">
            <div>
                <label style="font-size: 0.8rem; font-weight: 600;">Görünen Ad *</label>
                <input type="text" name="fields[__INDEX__][label]" class="form-control label-input" required
                    style="width: 100%; border-radius: 6px;">
            </div>
            <div>
                <label style="font-size: 0.8rem; font-weight: 600;">Sistem Kodu *</label>
                <input type="text" name="fields[__INDEX__][name]" class="form-control key-input" required
                    style="width: 100%; border-radius: 6px; font-family: monospace;">
            </div>
            <div>
                <label style="font-size: 0.8rem; font-weight: 600;">Veri Tipi</label>
                <select name="fields[__INDEX__][type]" class="form-control type-select"
                    style="width: 100%; border-radius: 6px;">
                    <option value="text">Metin</option>
                    <option value="number">Sayı</option>
                    <option value="date">Tarih</option>
                    <option value="textarea">Uzun Metin</option>
                </select>
            </div>
            <div style="padding-top: 20px;">
                <label style="font-size: 0.8rem; font-weight: 600;"><input type="checkbox"
                        name="fields[__INDEX__][required]" value="1" class="req-check"> Zorunlu</label>
            </div>
            <div style="padding-top: 20px;">
                <button type="button" class="btn btn-sm btn-outline-danger remove-field-btn"><i data-lucide="trash-2"
                        style="width: 16px;"></i></button>
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

            // Backend'den gelen mevcut fields verisi (JSON Decode edilmiş)
            const existingFields = @json($processTemplate->fields ?? []);

            function addRow(data = null) {
                const html = template.replace(/__INDEX__/g, fieldIndex);
                container.insertAdjacentHTML('beforeend', html);

                const newRow = container.lastElementChild;
                const labelInput = newRow.querySelector('.label-input');
                const keyInput = newRow.querySelector('.key-input');
                const typeSelect = newRow.querySelector('.type-select');
                const reqCheck = newRow.querySelector('.req-check');

                if (data) {
                    labelInput.value = data.label || '';
                    keyInput.value = data.name || '';
                    typeSelect.value = data.type || 'text';
                    if (data.required) reqCheck.checked = true;
                }

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
            }

            // Sayfa açıldığında veritabanındaki verileri çiz
            if (existingFields && existingFields.length > 0) {
                existingFields.forEach(f => addRow(f));
            }

            document.getElementById('addFieldBtn').addEventListener('click', () => addRow());

            container.addEventListener('click', function(e) {
                const btn = e.target.closest('.remove-field-btn');
                if (btn) btn.closest('.field-row').remove();
            });
        });
    </script>
@endpush
