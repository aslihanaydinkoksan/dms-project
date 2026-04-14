@extends('layouts.app')

@section('content')
    <div class="page-header flex-between mb-20"
        style="background: var(--surface-color); padding: 20px; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="background: #e0f2fe; color: #0284c7; padding: 12px; border-radius: 12px;">
                <i data-lucide="edit-3" style="width: 28px; height: 28px;"></i>
            </div>
            <div>
                <h1 class="page-title" style="margin-bottom: 5px; font-size: 1.4rem; color: var(--primary-color);">
                    {{ __('Belge Özelliklerini Düzenle') }}</h1>
                <p class="text-muted" style="margin: 0; font-size: 0.9rem;">
                    <strong>{{ $document->document_number }}</strong>
                    {{ __('kodlu belgenin üst verilerini (metadata) düzenliyorsunuz.') }}
                </p>
            </div>
        </div>
        <a href="{{ route('documents.show', $document->id) }}" class="btn btn-outline-secondary">
            <i data-lucide="arrow-left" style="width: 18px;"></i> {{ __('Belgeye Dön') }}
        </a>
    </div>

    @include('partials.alerts')

    <form action="{{ route('documents.update', $document->id) }}" method="POST" class="modern-form">
        @csrf
        @method('PUT')

        <div class="card glass-card"
            style="border-radius: var(--border-radius); border: 1px solid var(--border-color); background: var(--surface-color); box-shadow: var(--card-shadow); overflow: hidden; margin-bottom: 30px;">

            <div class="card-header"
                style="padding: 20px 25px; border-bottom: 1px solid var(--border-color); background: #f8fafc; font-weight: 600; font-size: 1.1rem; color: var(--primary-color); display: flex; align-items: center; gap: 8px;">
                <i data-lucide="info" style="color: var(--accent-color);"></i> {{ __('Temel Bilgiler') }}
            </div>

            <div class="card-body" style="padding: 30px;">
                <div class="alert alert-info"
                    style="margin-bottom: 25px; display: flex; gap: 10px; align-items: center; background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;">
                    <i data-lucide="alert-circle"></i>
                    <div>
                        <strong>{{ __('Not:') }}</strong>
                        {{ __('Buradan sadece belgenin sistem içi kimlik bilgileri değiştirilir. Fiziksel dosyayı veya onay akışını değiştirmek için belgenin "Revize Et" özelliğini kullanmalısınız.') }}
                    </div>
                </div>

                <div class="form-grid" style="display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600; display: flex; align-items: center; gap: 6px;">
                            {{ __('Belge Başlığı') }} <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="title" class="form-control" required
                            value="{{ old('title', $document->title) }}"
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                    </div>
                </div>

                <div class="form-grid"
                    style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600; display: flex; align-items: center; gap: 6px;">
                            {{ __('Bulunduğu Klasör') }} <span class="text-danger">*</span>
                            <i data-lucide="help-circle" style="width: 15px; color: #94a3b8; cursor: help;"
                                title="{{ __('Klasörü değiştirirseniz, sistem belgenin numarasını otomatik olarak yeni klasöre göre güncelleyecektir.') }}"></i>
                        </label>
                        <select name="folder_id" class="form-control" required
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                            @foreach ($flatFolders as $id => $path)
                                @php
                                    $parts = explode(' > ', $path);
                                    $depth = count($parts) - 1;
                                    $folderName = end($parts);
                                    $indent = $depth > 0 ? str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth) . '└─ ' : '';
                                @endphp
                                <option value="{{ $id }}"
                                    {{ old('folder_id', $document->folder_id) == $id ? 'selected' : '' }}>
                                    {!! $indent !!}{{ $folderName }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-warning" style="display: block; margin-top: 5px; font-size: 0.8rem;"><i
                                data-lucide="alert-triangle" style="width: 12px;"></i>
                            {{ __('Klasör değişirse Belge Kodu değişir!') }}</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600; display: flex; align-items: center; gap: 6px;">
                            {{ __('Gizlilik Seviyesi') }} <span class="text-danger">*</span>
                        </label>
                        <select name="privacy_level" class="form-control" required
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                            @foreach ($privacyLevels as $key => $label)
                                <option value="{{ $key }}"
                                    {{ old('privacy_level', $document->privacy_level) == $key ? 'selected' : '' }}>
                                    {{ __($label) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-grid"
                    style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600; display: flex; align-items: center; gap: 6px;">
                            {{ __('Doküman Tipi') }} <span class="text-danger">*</span>
                        </label>
                        <select name="document_type_id" id="documentTypeSelect" class="form-control" required
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                            <option value="">{{ __('-- Lütfen Seçiniz --') }}</option>
                            @foreach ($documentTypes as $type)
                                <option value="{{ $type->id }}"
                                    {{ old('document_type_id', $document->document_type_id ?? '') == $type->id ? 'selected' : '' }}>
                                    📄 {{ __($type->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600; display: flex; align-items: center; gap: 6px;">
                            {{ __('İlgili Departman (Opsiyonel)') }}
                        </label>
                        <select name="related_department_id" class="form-control"
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                            <option value="">{{ __('-- Genel (Tüm Şirket) --') }}</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}"
                                    {{ old('related_department_id', $document->related_department_id) == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="dynamic-custom-fields-container"
                    style="display: none; padding: 20px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; margin-bottom: 25px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                </div>
            </div>
        </div>

        <div class="card glass-card"
            style="border-radius: var(--border-radius); border: 1px solid var(--border-color); background: var(--surface-color); box-shadow: var(--card-shadow); overflow: hidden; margin-bottom: 30px;">
            <div class="card-header"
                style="padding: 20px 25px; border-bottom: 1px solid var(--border-color); background: #f8fafc; font-weight: 600; font-size: 1.1rem; color: var(--primary-color); display: flex; align-items: center; gap: 8px;">
                <i data-lucide="archive" style="color: var(--text-muted);"></i> {{ __('Saklama ve İmha Süreleri') }}
            </div>

            <div class="card-body" style="padding: 30px;">
                <div class="form-grid"
                    style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600;">{{ __('Bölümde Saklama Süresi (Yıl)') }} <span
                                class="text-danger">*</span></label>
                        <input type="number" name="department_retention_years" class="form-control"
                            value="{{ old('department_retention_years', $document->department_retention_years) }}"
                            min="0" required
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600;">{{ __('Arşivde Saklama Süresi (Yıl)') }}
                            <span class="text-danger">*</span></label>
                        <input type="number" name="archive_retention_years" class="form-control"
                            value="{{ old('archive_retention_years', $document->archive_retention_years) }}"
                            min="0" required
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                    </div>
                </div>

                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600;">{{ __('Geçerlilik Bitiş Tarihi') }}</label>
                        <input type="date" name="expire_at" class="form-control"
                            value="{{ old('expire_at', $document->expire_at ? \Carbon\Carbon::parse($document->expire_at)->format('Y-m-d') : '') }}"
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600;">{{ __('Etiketler (Opsiyonel)') }}</label>
                        @php
                            $selectedTags = $document->tags->pluck('id')->toArray();
                        @endphp
                        <select name="tags[]" class="form-control multiple-select" multiple size="3"
                            style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 6px;">
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}"
                                    {{ in_array($tag->id, old('tags', $selectedTags)) ? 'selected' : '' }}>
                                    {{ $tag->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions" style="display: flex; gap: 15px; justify-content: flex-end;">
            <a href="{{ route('documents.show', $document->id) }}" class="btn btn-outline-secondary"
                style="padding: 12px 25px;">{{ __('İptal Et') }}</a>
            <button type="submit" class="btn btn-primary" style="padding: 12px 35px; font-weight: bold;">
                <i data-lucide="save" style="width: 18px; margin-right: 5px;"></i> {{ __('Değişiklikleri Kaydet') }}
            </button>
        </div>
    </form>

    <script>
        window.existingMetadata = {!! json_encode($document->metadata ?? []) !!};
    </script>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();

            const typeSelect = document.getElementById('documentTypeSelect');
            const fieldsContainer = document.getElementById('dynamic-custom-fields-container');
            const existingMetadata = window.existingMetadata || {};

            if (typeSelect && fieldsContainer) {
                if (typeSelect.value) {
                    fetchFields(typeSelect.value);
                }

                typeSelect.addEventListener('change', function() {
                    fetchFields(this.value);
                });
            }

            function fetchFields(typeId) {
                fieldsContainer.innerHTML = '';
                fieldsContainer.style.display = 'none';

                if (!typeId) return;

                fieldsContainer.style.display = 'block';
                fieldsContainer.innerHTML =
                    '<div style="color:var(--text-muted); font-size:0.9rem; text-align:center;"><i data-lucide="loader" class="spin" style="width:18px; margin-right:5px; vertical-align:middle;"></i> {{ __('Özel alanlar yükleniyor...') }}</div>';
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }

                fetch(`/api/document-types/${typeId}/fields`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Veri çekilemedi.');
                        return response.json();
                    })
                    .then(fields => {
                        fieldsContainer.innerHTML = '';

                        if (fields && fields.length > 0) {
                            let html =
                                '<h4 style="font-size: 0.95rem; margin-bottom: 15px; color: var(--primary-color); display: flex; align-items: center; gap: 8px;"><i data-lucide="layers" style="width:16px;"></i> {{ __('Bu Tipe Özel Ek Bilgiler') }}</h4>';
                            html +=
                                '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">';

                            fields.forEach(field => {
                                const requiredAttr = field.required ? 'required' : '';
                                const requiredStar = field.required ?
                                    '<span class="text-danger">*</span>' : '';
                                const inputType = field.type || 'text';

                                const existingValue = existingMetadata[field.name] || '';

                                html += `
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; color: var(--secondary-color);">
                                        ${field.label} ${requiredStar}
                                    </label>
                                    <input type="${inputType}" 
                                           name="metadata[${field.name}]" 
                                           value="${existingValue}"
                                           class="form-control" 
                                           placeholder="${field.placeholder || ''}" 
                                           style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem;" 
                                           ${requiredAttr}>
                                </div>
                            `;
                            });

                            html += '</div>';
                            fieldsContainer.innerHTML = html;
                            if (typeof lucide !== 'undefined') {
                                lucide.createIcons();
                            }
                        } else {
                            fieldsContainer.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Dinamik alan hatası:', error);
                        fieldsContainer.innerHTML =
                            '<div class="text-danger" style="font-size:0.85rem;">{{ __('⚠️ Özel alanlar yüklenirken bir hata oluştu. Lütfen sayfayı yenileyin.') }}</div>';
                    });
            }
        });
    </script>
@endpush
