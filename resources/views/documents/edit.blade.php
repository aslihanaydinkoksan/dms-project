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
                        <label class="form-label" style="font-weight: 600;">{{ __('Belge Başlığı') }} <span
                                class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required
                            value="{{ old('title', $document->title) }}"
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                    </div>
                </div>

                <div class="form-grid"
                    style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600;">{{ __('Bulunduğu Klasör') }} <span
                                class="text-danger">*</span></label>
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
                        <small style="display: block; margin-top: 5px; color: #64748b; font-size: 0.75rem;">
                            <i data-lucide="corner-left-up"
                                style="width:12px; vertical-align:middle; margin-right:3px;"></i>
                            {{ __('Belgenin saklanacağı klasörü seçiniz.') }}
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600;">{{ __('Gizlilik Seviyesi') }} <span
                                class="text-danger">*</span></label>
                        <select name="privacy_level" id="privacyLevelSelect" class="form-control" required
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                            @foreach ($privacyLevels as $key => $label)
                                <option value="{{ $key }}"
                                    {{ old('privacy_level', $document->privacy_level) == $key ? 'selected' : '' }}>
                                    {{ __($label) }}</option>
                            @endforeach
                        </select>

                        {{-- Dinamik Açıklama Kutusu --}}
                        <div id="privacy-desc-box"
                            style="display: none; margin-top: 8px; padding: 12px; border-radius: 6px; background-color: #f0fdf4; border-left: 4px solid #22c55e; font-size: 0.85rem; color: #166534;">
                            <!-- JS ile dolacak -->
                        </div>
                        <small style="display: block; margin-top: 5px; color: #64748b; font-size: 0.75rem;">
                            <i data-lucide="corner-left-up"
                                style="width:12px; vertical-align:middle; margin-right:3px;"></i>
                            {{ __('Belgenin gizlilik seviyesini seçiniz.') }}
                        </small>
                    </div>
                </div>

                {{-- İlgili Departman siliğinde grid'in bozulmaması için tek sütun yapıldı --}}
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 30px;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600;">{{ __('Doküman Tipi') }} <span
                                class="text-danger">*</span></label>
                        <select name="document_type_id" id="documentTypeSelect" class="form-control" required
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                            <option value="">{{ __('-- Lütfen Seçiniz --') }}</option>
                            @foreach ($documentTypes as $type)
                                <option value="{{ $type->id }}"
                                    {{ old('document_type_id', $document->document_type_id) == $type->id ? 'selected' : '' }}>
                                    📄 {{ __($type->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="dynamic-custom-fields-container"
                    style="display: none; padding: 20px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; margin-bottom: 25px;">
                </div>
            </div>
        </div>

        <div class="card glass-card"
            style="border-radius: var(--border-radius); border: 1px solid var(--border-color); background: var(--surface-color); box-shadow: var(--card-shadow); overflow: hidden; margin-bottom: 30px;">
            <div class="card-header"
                style="padding: 20px 25px; border-bottom: 1px solid var(--border-color); background: #f8fafc; font-weight: 600; font-size: 1.1rem; color: var(--primary-color);">
                <i data-lucide="archive" style="color: var(--text-muted);"></i> {{ __('Saklama ve İmha Süreleri') }}
            </div>

            <div class="card-body" style="padding: 30px;">
                <div class="form-grid"
                    style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label class="form-label"
                            style="font-weight: 600;">{{ __('Bölümde Saklama Süresi (Yıl)') }}</label>
                        <input type="number" name="department_retention_years" class="form-control"
                            value="{{ old('department_retention_years', $document->department_retention_years) }}"
                            min="0"
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                    </div>

                    <div class="form-group">
                        <label class="form-label"
                            style="font-weight: 600;">{{ __('Arşivde Saklama Süresi (Yıl)') }}</label>
                        <input type="number" name="archive_retention_years" class="form-control"
                            value="{{ old('archive_retention_years', $document->archive_retention_years) }}"
                            min="0"
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
                        @php $selectedTags = $document->tags->pluck('id')->toArray(); @endphp
                        <select name="tags[]" id="visionaryTagsEdit" class="form-control" multiple="multiple"
                            style="width: 100%;">
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}"
                                    {{ in_array($tag->id, old('tags', $selectedTags)) ? 'selected' : '' }}>
                                    {{ $tag->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted"
                            style="display: block; margin-top: 8px; font-size: 0.85rem; background: #f8fafc; padding: 10px; border-radius: 6px; border-left: 3px solid #3498db;">
                            💡 {{ __('Etiket eklemek için yazıp Enter\'a basın.') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions" style="display: flex; gap: 15px; justify-content: flex-end; margin-bottom: 50px;">
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
    {{-- Select2 ve jQuery motorlarını buraya da ekliyoruz --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        .select2-container .select2-selection--multiple {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 5px;
            min-height: 45px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #eef2ff;
            border: 1px solid #c7d2fe;
            color: #4f46e5;
            border-radius: 4px;
            padding: 4px 8px;
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();

            // Etiketleri Vizyoner Yapıyoruz
            $(document).ready(function() {
                $('#visionaryTagsEdit').select2({
                    placeholder: "{{ __('Etiketleri düzenleyin...') }}",
                    allowClear: true,
                    tags: true,
                    tokenSeparators: [',']
                });
            });

            // GİZLİLİK SEVİYESİ AÇIKLAMA MOTORU (Yeni Eklendi)
            const privacySelect = document.getElementById('privacyLevelSelect');
            const descBox = document.getElementById('privacy-desc-box');

            const privacyDescriptions = {
                'public': 'Şirketteki <strong>herkes</strong> görebilir. Belgenin hangi departman klasöründe olduğu fark etmez.',
                'confidential': 'Sadece <strong>bu klasörün ait olduğu departmanda çalışanlar</strong> görebilir. Diğer departmanlara kapalıdır.',
                'strictly_confidential': 'Kendi departmanınızın klasöründe olsa bile, sadece <strong>özel yetki verilmiş kişiler</strong> görebilir.',
                'board_only': 'Sadece <strong>Yönetim Kurulu</strong> üyeleri görebilir. Şirketteki başka hiç kimse erişemez.'
            };

            if (privacySelect) {
                function updatePrivacyDesc() {
                    const selected = privacySelect.value;
                    if (selected) {
                        descBox.style.display = 'block';
                        const explanation = privacyDescriptions[selected] ||
                            'Bu gizlilik seviyesine atanan özel yetkilere sahip kişiler dışında kimse bu belgeyi göremez.';
                        descBox.innerHTML =
                            `<i data-lucide="shield-check" style="width:16px; vertical-align:middle; margin-right:4px;"></i> ${explanation}`;
                        lucide.createIcons();
                    } else {
                        descBox.style.display = 'none';
                    }
                }

                privacySelect.addEventListener('change', updatePrivacyDesc);
                updatePrivacyDesc(); // Sayfa yüklendiğinde mevcut seçime göre kutuyu aç
            }

            // Dinamik Alanlar Fetch Mekanizması
            const typeSelect = document.getElementById('documentTypeSelect');
            const fieldsContainer = document.getElementById('dynamic-custom-fields-container');
            const existingMetadata = window.existingMetadata || {};

            if (typeSelect && fieldsContainer) {
                if (typeSelect.value) fetchFields(typeSelect.value);
                typeSelect.addEventListener('change', function() {
                    fetchFields(this.value);
                });
            }

            function fetchFields(typeId) {
                fieldsContainer.innerHTML =
                    '<div class="text-center"><i data-lucide="loader" class="spin"></i> {{ __('Yükleniyor...') }}</div>';
                fieldsContainer.style.display = 'block';
                lucide.createIcons();

                fetch(`{{ url('/api/document-types') }}/${typeId}/fields`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(fields => {
                        fieldsContainer.innerHTML = '';
                        if (fields && fields.length > 0) {
                            let html =
                                '<h4 style="margin-bottom:15px; font-size:0.95rem;">{{ __('Özel Alanlar') }}</h4><div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">';
                            fields.forEach(field => {
                                const val = existingMetadata[field.name] || '';
                                html += `<div class="form-group">
                                <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:5px;">${field.label}</label>
                                <input type="${field.type || 'text'}" name="metadata[${field.name}]" value="${val}" class="form-control" style="width:100%; padding:10px; border:1px solid var(--border-color); border-radius:6px;" ${field.required ? 'required' : ''}>
                            </div>`;
                            });
                            html += '</div>';
                            fieldsContainer.innerHTML = html;
                        } else {
                            fieldsContainer.style.display = 'none';
                        }
                    });
            }
        });
    </script>
@endpush
