@extends('layouts.app')

@section('content')
    <div class="page-header"
        style="background: var(--surface-color); padding: 25px; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--card-shadow); margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
        <div style="background: #eef2ff; color: var(--accent-color); padding: 15px; border-radius: 12px;">
            <i data-lucide="file-up" style="width: 32px; height: 32px;"></i>
        </div>
        <div>
            <h1 class="page-title" style="margin-bottom: 5px; font-size: 1.5rem; color: var(--primary-color);">
                {{ __('Yeni Belge Yükle') }}</h1>
            <p class="text-muted" style="margin: 0;">{{ __('Lütfen belge detaylarını ve onay akışını eksiksiz doldurun.') }}
            </p>
        </div>
    </div>

    @include('partials.alerts')

    <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" class="modern-form"
        id="documentUploadForm">
        @csrf

        <div class="layout-split" style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px; align-items: start;">

            <div class="card glass-card"
                style="border-radius: var(--border-radius); border: 1px solid var(--border-color); background: var(--surface-color); box-shadow: var(--card-shadow); overflow: hidden;">
                <div class="card-header"
                    style="padding: 20px 25px; border-bottom: 1px solid var(--border-color); background: #f8fafc; font-weight: 600; font-size: 1.1rem; color: var(--primary-color); display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="info" style="color: var(--accent-color);"></i> {{ __('Belge Üst Verileri (Metadata)') }}
                </div>

                <div class="card-body" style="padding: 30px;">

                    <div class="form-group" style="margin-bottom: 30px;">
                        <label class="form-label"
                            style="font-weight: 600; color: var(--secondary-color); margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            {{ __('Yüklenecek Dosya') }} <span class="text-danger">*</span>
                            <i data-lucide="help-circle" style="width: 15px; color: #94a3b8; cursor: help;"
                                title="{{ __('Sisteme yüklenecek asıl fiziksel dosyadır. Birden fazla dosya yüklemek için önce bir klasör oluşturun.') }}"></i>
                        </label>
                        <div class="file-upload-wrapper" style="position: relative; width: 100%;">
                            <input type="file" name="file" id="file" class="file-upload-input" required
                                style="position: absolute; margin: 0; padding: 0; width: 100%; height: 100%; outline: none; opacity: 0; cursor: pointer; z-index: 2;">
                            <label for="file" class="file-upload-label"
                                style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 20px; background-color: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; color: var(--text-muted); transition: all 0.3s ease; z-index: 1;">
                                <i data-lucide="upload-cloud"
                                    style="width: 48px; height: 48px; color: var(--accent-color); margin-bottom: 15px; opacity: 0.8;"></i>
                                <span id="file-name-display"
                                    style="font-size: 1.1rem; text-align: center;">{{ __('Dosya Seçin veya Sürükleyin') }}</span>
                                <span
                                    style="font-size: 0.85rem; color: #94a3b8; margin-top: 8px;">{{ __('Maksimum dosya boyutu: 20MB (PDF, DOCX, JPG)') }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-grid"
                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label class="form-label"
                                style="font-weight: 600; display: flex; align-items: center; gap: 6px;">
                                {{ __('Belge Başlığı') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="title" class="form-control" required
                                placeholder="{{ __('Örn: 2026 Q1 Bütçe Raporu') }}"
                                style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                        </div>
                    </div>

                    <div class="form-grid"
                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label class="form-label"
                                style="font-weight: 600; display: flex; align-items: center; gap: 6px;">
                                {{ __('Hedef Klasör') }} <span class="text-danger">*</span>
                                <i data-lucide="help-circle" style="width: 15px; color: #94a3b8; cursor: help;"
                                    title="{{ __('Belge bu klasörün içine yerleşecek ve bu klasörün güvenlik izinlerini miras alacaktır.') }}"></i>
                            </label>
                            <select name="folder_id" class="form-control" required
                                style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                                <option value="">{{ __('-- Klasör Seçiniz --') }}</option>
                                @foreach ($flatFolders as $id => $path)
                                    @php
                                        $parts = explode(' > ', $path);
                                        $depth = count($parts) - 1;
                                        $folderName = end($parts);
                                        $indent =
                                            $depth > 0 ? str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth) . '└─ ' : '';
                                    @endphp
                                    <option value="{{ $id }}"
                                        {{ request('folder_id') == $id ? 'selected' : '' }}>
                                        {!! $indent !!}{{ $folderName }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted"
                                style="display: block; margin-top: 5px; font-size: 0.8rem;">{{ __('Belgenin sistemde fiziksel olarak duracağı dizin.') }}</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label"
                                style="font-weight: 600; display: flex; align-items: center; gap: 6px;">
                                {{ __('Gizlilik Seviyesi') }} <span class="text-danger">*</span>
                                <i data-lucide="help-circle" style="width: 15px; color: #94a3b8; cursor: help;"
                                    title="{{ __('Bu ayar, klasör yetkilerini ezen ekstra bir kalkan görevi görür.') }}"></i>
                            </label>
                            <select name="privacy_level" class="form-control" required
                                style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                                @foreach ($privacyLevels as $key => $label)
                                    <option value="{{ $key }}">{{ __($label) }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted"
                                style="display: block; margin-top: 5px; font-size: 0.8rem;">{{ __('Bu belgenin kimlere görüneceğini kısıtlar.') }}</small>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label
                            style="font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: var(--secondary-color); display: block;">
                            {{ __('Doküman Tipi') }} <span class="text-danger">*</span>
                        </label>
                        <select name="document_type_id" id="documentTypeSelect" class="form-control" required
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                            <option value="">{{ __('-- Lütfen Seçiniz --') }}</option>
                            @foreach ($documentTypes as $type)
                                <option value="{{ $type->id }}"
                                    {{ old('document_type_id') == $type->id ? 'selected' : '' }}>
                                    📄 {{ __($type->name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="dynamic-custom-fields-container"
                        style="display: none; padding: 20px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; margin-bottom: 25px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600; display: flex; align-items: center; gap: 6px;">
                            {{ __('İlgili Departman (Opsiyonel)') }}
                            <i data-lucide="help-circle" style="width: 15px; color: #94a3b8; cursor: help;"
                                title="{{ __('Belge belirli bir departmanın iç işleyişini ilgilendiriyorsa seçilmelidir.') }}"></i>
                        </label>
                        <select name="related_department_id" class="form-control"
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                            <option value="">{{ __('-- Genel (Tüm Şirket) --') }}</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted"
                            style="display: block; margin-top: 5px; font-size: 0.8rem;">{{ __('Belge sadece bir departmana aitse seçin, aksi halde boş bırakın.') }}</small>
                    </div>
                </div>

                <div style="height: 1px; background: var(--border-color); margin: 30px 0;"></div>

                <h3 class="section-title"
                    style="color: var(--primary-color); font-size: 1.1rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="archive" style="color: var(--text-muted);"></i> {{ __('Saklama ve İmha Süreleri') }}
                </h3>

                <div class="form-grid"
                    style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600;">{{ __('Bölümde Saklama Süresi (Yıl)') }}
                            <span class="text-danger">*</span></label>
                        <input type="number" name="department_retention_years" class="form-control" value="1"
                            min="0" required
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                        <small class="text-muted"
                            style="display: block; margin-top: 5px; font-size: 0.8rem;">{{ __('Aktif olarak departmanda kaç yıl kullanılacak?') }}</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600;">{{ __('Arşivde Saklama Süresi (Yıl)') }}
                            <span class="text-danger">*</span></label>
                        <input type="number" name="archive_retention_years" class="form-control" value="5"
                            min="0" required
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                        <small class="text-muted"
                            style="display: block; margin-top: 5px; font-size: 0.8rem;">{{ __('Pasife düştükten sonra arşivde (depoda) kaç yıl kalacak?') }}</small>
                    </div>
                </div>

                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600;">{{ __('Geçerlilik Bitiş Tarihi') }}</label>
                        <input type="date" name="expire_at" class="form-control"
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                        <small class="text-muted"
                            style="display: block; margin-top: 5px; font-size: 0.8rem;">{{ __('Süreli bir sözleşme veya formsa bitiş tarihini girin.') }}</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600;">{{ __('Etiketler (Opsiyonel)') }}</label>
                        <select name="tags[]" class="form-control multiple-select" multiple size="3"
                            style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 6px;">
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted"
                            style="display: block; margin-top: 5px; font-size: 0.8rem;">{{ __('Birden fazla seçmek için CTRL/CMD tuşuna basılı tutun.') }}</small>
                    </div>
                </div>

            </div>
        </div>

        <div class="card glass-card"
            style="border-radius: var(--border-radius); border: 1px solid var(--border-color); background: var(--surface-color); box-shadow: var(--card-shadow); position: sticky; top: 20px;">
            <div class="card-header"
                style="padding: 20px 25px; border-bottom: 1px solid var(--border-color); background: #f8fafc; font-weight: 600; font-size: 1.1rem; color: var(--primary-color); display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="git-merge" style="color: var(--accent-color);"></i> {{ __('Onay Akışı') }}
                </div>
                <button type="button" id="add-approver-btn" class="btn btn-sm btn-outline-primary"
                    style="display: flex; align-items: center; gap: 4px; padding: 6px 12px;">
                    <i data-lucide="plus" style="width: 14px;"></i> {{ __('Onaycı Ekle') }}
                </button>
            </div>

            <div class="card-body" style="padding: 25px;">
                <div class="alert alert-info"
                    style="font-size: 0.85rem; padding: 12px; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; border-radius: 6px; margin-bottom: 20px; display: flex; gap: 10px; align-items: flex-start;">
                    <i data-lucide="info" style="width: 24px; flex-shrink: 0;"></i>
                    <span>{{ __('Sıralı onay için Adım Numaralarını (1, 2, 3..) ardışık verin. Paralel (aynı anda) onay için aynı adım numarasını kullanın.') }}</span>
                </div>

                <div id="workflow-container" style="display: flex; flex-direction: column; gap: 15px;">
                    <div class="workflow-empty-state text-muted text-center" id="empty-workflow-msg"
                        style="padding: 30px 10px; border: 1px dashed var(--border-color); border-radius: 8px; background: #f8fafc;">
                        <i data-lucide="users"
                            style="width: 32px; height: 32px; opacity: 0.3; margin-bottom: 10px; display: block; margin: 0 auto;"></i>
                        <span
                            style="font-size: 0.9rem;">{{ __('Bu belge için onaycı seçilmedi.') }}<br><strong>{{ __('Belge direkt yayına alınır.') }}</strong></span>
                    </div>
                </div>

                <div style="height: 1px; background: var(--border-color); margin: 25px 0;"></div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block"
                        style="width: 100%; padding: 15px; font-size: 1.1rem; justify-content: center; display: flex; align-items: center; gap: 10px; font-weight: bold;">
                        <i data-lucide="rocket" style="width: 20px;"></i> {{ __('Belgeyi Sisteme Yükle') }}
                    </button>
                </div>
            </div>
        </div>

        </div>
    </form>

    <template id="approver-row-template">
        <div class="workflow-row"
            style="display: flex; gap: 10px; align-items: flex-start; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
            <div class="workflow-input-group" style="flex: 2;">
                <label
                    style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 5px;">{{ __('Onaycı Seçimi') }}</label>
                <select name="approvers[__INDEX__][user_id]" class="form-control" required
                    style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                    <option value="">{{ __('-- Kişi Seç --') }}</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}
                            ({{ $user->department->name ?? __('Dept Yok') }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="workflow-input-group step-group" style="flex: 1;">
                <label
                    style="display: flex; align-items: center; gap: 4px; font-size: 0.8rem; font-weight: 600; margin-bottom: 5px;">
                    {{ __('Adım') }}
                    <i data-lucide="help-circle" style="width: 12px; color: #94a3b8; cursor: help;"
                        title="{{ __('Önce 1. adımdakilere bildirim gider, onlar onaylayınca 2. adıma geçer.') }}"></i>
                </label>
                <input type="number" name="approvers[__INDEX__][step_order]" class="form-control text-center"
                    min="1" value="1" required
                    style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; text-align: center;">
            </div>
            <div class="workflow-action" style="margin-top: 23px;">
                <button type="button" class="btn btn-outline-danger remove-approver" title="{{ __('Onaycıyı Sil') }}"
                    style="padding: 10px 12px; height: 42px;">
                    <i data-lucide="trash-2" style="width: 16px; pointer-events: none;"></i>
                </button>
            </div>
        </div>
    </template>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons(); // İkonları yükle

            // --- 1. DOSYA SEÇİCİ (FILE UPLOAD) UI KONTROLÜ ---
            const fileInput = document.getElementById('file');
            const fileNameDisplay = document.getElementById('file-name-display');
            const fileLabel = document.querySelector('.file-upload-label');

            if (fileInput && fileNameDisplay) {
                fileInput.addEventListener('change', function(e) {
                    if (e.target.files.length > 0) {
                        fileNameDisplay.textContent = e.target.files[0].name;
                        fileNameDisplay.style.color = 'var(--success-color)';
                        fileNameDisplay.style.fontWeight = 'bold';

                        const iconEl = fileLabel.querySelector('i');
                        iconEl.setAttribute('data-lucide', 'check-circle-2');
                        iconEl.style.color = 'var(--success-color)';

                        fileLabel.style.borderColor = 'var(--success-color)';
                        fileLabel.style.background = '#f0fdf4';

                        lucide.createIcons();
                    } else {
                        fileNameDisplay.textContent = '{{ __('Dosya Seçin veya Sürükleyin') }}';
                        fileNameDisplay.style.color = 'var(--text-muted)';
                        fileNameDisplay.style.fontWeight = 'normal';

                        const iconEl = fileLabel.querySelector('i');
                        iconEl.setAttribute('data-lucide', 'upload-cloud');
                        iconEl.style.color = 'var(--accent-color)';

                        fileLabel.style.borderColor = '#cbd5e1';
                        fileLabel.style.background = '#f8fafc';

                        lucide.createIcons();
                    }
                });

                fileInput.addEventListener('dragenter', () => {
                    fileLabel.style.borderColor = 'var(--accent-color)';
                    fileLabel.style.background = '#eef2ff';
                });
                fileInput.addEventListener('dragleave', () => {
                    if (fileInput.files.length === 0) {
                        fileLabel.style.borderColor = '#cbd5e1';
                        fileLabel.style.background = '#f8fafc';
                    }
                });
                fileInput.addEventListener('drop', () => {
                    fileLabel.style.borderColor = 'var(--success-color)';
                    fileLabel.style.background = '#f0fdf4';
                });
            }

            // --- 2. DİNAMİK ONAY AKIŞI (WORKFLOW) SATIR YÖNETİMİ ---
            const container = document.getElementById('workflow-container');
            const addBtn = document.getElementById('add-approver-btn');
            const templateEl = document.getElementById('approver-row-template');
            const emptyMsg = document.getElementById('empty-workflow-msg');
            let approverIndex = 0;

            if (addBtn && templateEl && container) {
                const template = templateEl.innerHTML;

                addBtn.addEventListener('click', function() {
                    if (emptyMsg) emptyMsg.style.display = 'none';
                    const rowHtml = template.replace(/__INDEX__/g, approverIndex);
                    container.insertAdjacentHTML('beforeend', rowHtml);
                    approverIndex++;
                    lucide.createIcons();
                });

                container.addEventListener('click', function(e) {
                    const removeBtn = e.target.closest('.remove-approver');
                    if (removeBtn) {
                        const row = removeBtn.closest('.workflow-row');
                        row.remove();
                        if (container.querySelectorAll('.workflow-row').length === 0 && emptyMsg) {
                            emptyMsg.style.display = 'block';
                        }
                    }
                });
            }

            // --- 3. YENİ DİNAMİK FORM ALANLARI (CUSTOM FIELDS) MOTORU ---
            const typeSelect = document.getElementById('documentTypeSelect');
            const fieldsContainer = document.getElementById('dynamic-custom-fields-container');

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

                fetch(`{{ url('/api/document-types') }}/${typeId}/fields`, {
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

                                html += `
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; color: var(--secondary-color);">
                                        ${field.label} ${requiredStar}
                                    </label>
                                    <input type="${inputType}" 
                                           name="metadata[${field.name}]" 
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
