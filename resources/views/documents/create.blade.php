@extends('layouts.app')
<style>
    @keyframes alert-pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(186, 230, 253, 0.7);
            border-color: #bae6fd;
        }

        50% {
            box-shadow: 0 0 12px 4px rgba(186, 230, 253, 0.4);
            border-color: #38bdf8;
            /* Kenarlık rengi biraz daha belirginleşir */
            background-color: #f0f9ff;
            /* Arka plan hafifçe aydınlanır */
        }

        100% {
            box-shadow: 0 0 0 0 rgba(186, 230, 253, 0);
            border-color: #bae6fd;
        }
    }

    .attention-getter {
        animation: alert-pulse 2s infinite ease-in-out;
        transition: all 0.3s ease;
    }
</style>

@section('content')
    {{-- 1. HEADER VE BREADCRUMB (ORİJİNAL YAPIN) --}}
    <div class="page-header"
        style="background: var(--surface-color); padding: 25px; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--card-shadow); margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
        <div style="background: #eef2ff; color: var(--accent-color); padding: 15px; border-radius: 12px;">
            <i data-lucide="copy-plus" style="width: 32px; height: 32px;"></i>
        </div>
        <div>
            <h1 class="page-title" style="margin-bottom: 5px; font-size: 1.5rem; color: var(--primary-color);">
                {{ __('Belge Yükleme ') }}</h1>
            <p class="text-muted" style="margin: 0;">
                {{ __('Aynı anda birden fazla dosya seçebilir ve her biri için ayrı detaylar girebilirsiniz.') }}
            </p>
        </div>
    </div>

    @include('partials.alerts')

    {{-- 40MB BOYUT KALKANI UYARISI --}}
    <div id="size-alert" class="alert alert-danger"
        style="display: none; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 15px; border-radius: 8px; margin-bottom: 20px; align-items: center; gap: 10px;">
        <i data-lucide="alert-triangle" style="width: 20px;"></i>
        <span id="size-alert-text"></span>
    </div>

    <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" class="modern-form"
        id="documentUploadForm">
        @csrf
        <div class="layout-split" style="display: flex; flex-wrap: wrap; gap: 25px; align-items: flex-start;">

            {{-- SOL TARAF: FORM KARTI --}}
            <div class="card glass-card"
                style="flex: 1 1 65%; min-width: 300px; border-radius: var(--border-radius); border: 1px solid var(--border-color); background: var(--surface-color); box-shadow: var(--card-shadow); overflow: hidden;">
                <div class="card-header"
                    style="padding: 20px 25px; border-bottom: 1px solid var(--border-color); background: #f8fafc; font-weight: 600; font-size: 1.1rem; color: var(--primary-color); display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="layers" style="color: var(--accent-color);"></i>
                    {{ __('Dosya Seçimi ve Belge Kartları') }}
                </div>

                <div class="card-body" style="padding: 30px;">
                    {{-- ÇOKLU DOSYA SEÇİCİ --}}
                    <div class="form-group" style="margin-bottom: 30px;">
                        <label class="form-label"
                            style="font-weight: 600; color: var(--secondary-color); margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            {{ __('Dosyaları Yükle') }} <span class="text-danger">*</span>
                        </label>
                        <div class="file-upload-wrapper" style="position: relative; width: 100%;">
                            <input type="file" name="files[]" id="file" class="file-upload-input" multiple required
                                style="position: absolute; margin: 0; padding: 0; width: 100%; height: 100%; outline: none; opacity: 0; cursor: pointer; z-index: 2;">
                            <label for="file" class="file-upload-label"
                                style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 20px; background-color: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; color: var(--text-muted); transition: all 0.3s ease; z-index: 1;">
                                <i data-lucide="upload-cloud"
                                    style="width: 48px; height: 48px; color: var(--accent-color); margin-bottom: 15px; opacity: 0.8;"></i>
                                <span id="file-name-display"
                                    style="font-size: 1.1rem; text-align: center;">{{ __('Dosyaları Seçin veya Buraya Sürükleyin') }}</span>
                                <span
                                    style="font-size: 0.85rem; color: #94a3b8; margin-top: 8px;">{{ __('Toplam Limit: 40MB | Her belge için ayrı bir veri kartı oluşturulacaktır.Toplu seçim için CTRL tuşuna basılı tutun VEYA dosyaları tek tek seçip listeye ekleyin.') }}</span>
                            </label>
                        </div>
                    </div>

                    {{-- DİNAMİK KARTLAR (Her dosya için oluşacak) --}}
                    <div id="dynamic-cards-container"
                        style="display: flex; flex-direction: column; gap: 20px; margin-bottom: 30px;">
                        {{-- JavaScript ile burası dolacak --}}
                    </div>

                    <div style="height: 1px; background: var(--border-color); margin: 30px 0;"></div>

                    {{-- GLOBAL AYARLAR: KLASÖR, GİZLİLİK VE DEPARTMAN (ORİJİNAL) --}}
                    <h3 class="section-title"
                        style="color: var(--primary-color); font-size: 1.1rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="settings-2" style="color: var(--text-muted);"></i>
                        {{ __(' Güvenlik ve Klasör Ayarları') }}
                    </h3>

                    <div class="form-grid"
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 600;">{{ __('Hedef Klasör') }} <span
                                    class="text-danger">*</span></label>
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
                                    <option value="{{ $id }}">{!! $indent !!}{{ $folderName }}
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
                            <label class="form-label" style="font-weight: 600;">
                                {{ __('Gizlilik Seviyesi') }} <span class="text-danger">*</span>
                            </label>

                            {{-- id="privacyLevelSelect" eklendi --}}
                            <select name="privacy_level" id="privacyLevelSelect" class="form-control" required
                                style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                                <option value="">{{ __('-- Gizlilik Seviyesi Seçin --') }}</option>
                                @foreach ($privacyLevels as $key => $label)
                                    <option value="{{ $key }}">{{ __($label) }}</option>
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

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label" style="font-weight: 600; display: flex; align-items: center; gap: 6px;">
                            {{ __('Bilgi Verilecek Kullanıcılar') }}
                            <i data-lucide="bell-ring" style="width: 15px; color: var(--accent-color);"></i>
                        </label>
                        <select name="notified_user_ids[]" id="notifiableSuperiorsSelect" class="form-control"
                            multiple="multiple" style="width: 100%;">
                            @foreach ($notifiableSuperiors as $departmentName => $deptUsers)
                                <optgroup label="{{ $departmentName }}">
                                    @foreach ($deptUsers as $superior)
                                        <option value="{{ $superior->id }}">{{ $superior->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <h3 class="section-title"
                        style="color: var(--primary-color); font-size: 1.1rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="archive" style="color: var(--text-muted);"></i>
                        {{ __('Saklama ve İmha Politikası') }}
                    </h3>
                    <div class="form-grid"
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 600;">{{ __('Bölümde Saklama (Yıl)') }}</label>
                            <input type="number" name="department_retention_years" class="form-control" min="0"
                                placeholder="1"
                                style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 600;">{{ __('Arşivde Saklama (Yıl)') }}</label>
                            <input type="number" name="archive_retention_years" class="form-control" min="0"
                                placeholder="5"
                                style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label"
                            style="font-weight: 600;">{{ __('Etiketler (Tümüne Uygulanır)') }}</label>
                        <select name="tags[]" id="visionaryTags" class="form-control" multiple="multiple"
                            style="width: 100%;">
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- SAĞ TARAF: ONAY AKIŞI KARTI (ORİJİNAL) --}}
            <div class="card glass-card"
                style="flex: 1 1 50%; min-width: 300px; border-radius: var(--border-radius); border: 1px solid var(--border-color); background: var(--surface-color); box-shadow: var(--card-shadow); position: sticky; top: 20px;">
                <div class="card-header"
                    style="padding: 20px 25px; border-bottom: 1px solid var(--border-color); background: #f8fafc; font-weight: 600; font-size: 1.1rem; color: var(--primary-color); display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 8px;"> <i data-lucide="git-merge"
                            style="color: var(--accent-color);"></i> {{ __('Ortak Onay Akışı') }} </div>
                    <button type="button" id="add-approver-btn" class="btn btn-sm btn-outline-primary">
                        <i data-lucide="plus" style="width: 14px;"></i> {{ __('Onaycı Ekle') }}
                    </button>
                </div>
                <div class="card-body" style="padding: 25px;">
                    <div class="alert alert-info attention-getter"
                        style="font-size: 0.8rem; padding: 10px; margin-bottom: 15px; border-radius: 6px; background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;">
                        <i data-lucide="info" style="width:14px; vertical-align:middle;"></i>
                        <strong>Not:</strong> Belge bazlı farklı onaycılar gerekiyorsa belgeleri ayrı ayrı yükleyiniz.
                    </div>
                    <div id="workflow-container" style="display: flex; flex-direction: column; gap: 15px;">
                        <div class="workflow-empty-state text-muted text-center" id="empty-workflow-msg"
                            style="padding: 30px 10px; border: 1px dashed var(--border-color); border-radius: 8px; background: #f8fafc;">
                            <i data-lucide="users"
                                style="width: 32px; height: 32px; opacity: 0.3; margin-bottom: 10px; display: block; margin: 0 auto;"></i>
                            <span>{{ __('Onaycı seçilmezse belgeler direkt yayınlanır.') }}</span>
                        </div>
                    </div>
                    <div style="height: 1px; background: var(--border-color); margin: 25px 0;"></div>
                    <button type="submit" id="mainSubmitBtn" class="btn btn-primary btn-block"
                        style="width: 100%; padding: 15px; font-size: 1.1rem; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 10px;">
                        <i data-lucide="rocket" style="width: 20px;"></i> {{ __('Yükle') }}
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- ONAYCI ROW TEMPLATE (ORİJİNAL) --}}
    <template id="approver-row-template">
        <div class="workflow-row"
            style="display: flex; gap: 10px; align-items: flex-end; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
            <div style="flex: 2;">
                <label style="font-size: 0.75rem; font-weight:600; margin-bottom:4px; display:block;">Onaycı</label>
                <select name="approvers[__INDEX__][user_id]" class="form-control approver-select" required>
                    <option value="">{{ __('-- Kişi Seç --') }}</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->department->name ?? 'Genel' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="flex: 1;">
                <label style="font-size: 0.75rem; font-weight:600; margin-bottom:4px; display:block;">Adım</label>
                <input type="number" name="approvers[__INDEX__][step_order]" class="form-control text-center"
                    min="1" value="1" required style="height: 42px;">
            </div>
            <button type="button" class="btn btn-outline-danger remove-approver" style="padding: 0 15px; height: 42px;">
                <i data-lucide="trash-2" style="width: 16px;"></i>
            </button>
        </div>
    </template>
@endsection

@push('scripts')
    {{-- Kütüphaneler (Orijinal) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        {{-- Orijinal Stil Tanımların --}} .select2-container .select2-selection--multiple {
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
            margin-top: 5px;
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

            {{-- Orijinal Init Kodların --}}
            $('#visionaryTags').select2({
                placeholder: "Etiket yazın veya seçin...",
                allowClear: true,
                tags: true
            });
            $('#notifiableSuperiorsSelect').select2({
                placeholder: "-- Yönetici seçiniz --",
                allowClear: true,
                width: '100%'
            });

            const fileInput = document.getElementById('file');
            const fileNameDisplay = document.getElementById('file-name-display');
            const cardsContainer = document.getElementById('dynamic-cards-container');
            const alertBox = document.getElementById('size-alert');
            const alertText = document.getElementById('size-alert-text');
            const submitBtn = document.getElementById('mainSubmitBtn');
            const docTypes = @json($documentTypes);

            {{-- ÇOKLU DOSYA VE DİNAMİK KART MOTORU --}}
            // --- ÇOKLU DOSYA BİRİKTİRİCİ VE DİNAMİK KART MOTORU ---
            if (fileInput) {
                // YENİ: Dosyaları üst üste biriktireceğimiz sanal kutu
                const dataTransfer = new DataTransfer();

                fileInput.addEventListener('change', function() {
                    let totalSize = 0;

                    // 1. Yeni seçilen dosyaları sanal kutuya ekle (Üst üste biriktir)
                    Array.from(this.files).forEach(file => {
                        // Aynı dosya yanlışlıkla 2 kez seçilirse engelle
                        let exists = false;
                        for (let i = 0; i < dataTransfer.items.length; i++) {
                            if (dataTransfer.items[i].getAsFile().name === file.name) exists = true;
                        }
                        if (!exists) dataTransfer.items.add(file);
                    });

                    // 2. Input'un asıl değerini birikmiş liste ile güncelle
                    this.files = dataTransfer.files;

                    // 3. Birikmiş tüm dosyaları listeye alıp boyutu hesapla
                    const allFiles = Array.from(this.files);
                    allFiles.forEach(file => totalSize += file.size);
                    const totalSizeMB = (totalSize / (1024 * 1024)).toFixed(2);

                    if (totalSize > 41943040) { // 40MB LIMIT
                        alertBox.style.display = 'flex';
                        alertText.innerHTML =
                            `<strong>Boyut Limiti Aşıldı!</strong> Toplam: <strong>${totalSizeMB} MB</strong> seçtiniz. Maksimum 40 MB yükleyebilirsiniz.`;
                        submitBtn.disabled = true;
                        cardsContainer.innerHTML = '';

                        // Limit aşılırsa son eklenenleri geri al (isteğe bağlı güvenlik)
                        dataTransfer.items.clear();
                        this.files = dataTransfer.files;
                        fileNameDisplay.textContent = 'Dosyalar silindi, lütfen tekrar seçin';
                        fileNameDisplay.style.color = 'var(--text-muted)';
                    } else {
                        alertBox.style.display = 'none';
                        submitBtn.disabled = false;
                        cardsContainer.innerHTML = '';

                        if (allFiles.length > 0) {
                            fileNameDisplay.textContent = allFiles.length +
                                ' Dosya Eklendi (Daha fazla ekleyebilirsiniz)';
                            fileNameDisplay.style.color = 'var(--success-color)';
                        }

                        // KARTLARI ÜRET
                        allFiles.forEach((file, index) => {
                            let options = '<option value="">-- Tip Seçin --</option>';
                            docTypes.forEach(t => {
                                options +=
                                    `<option value="${t.id}" data-req-exp="${t.requires_expiration_date ? 'true' : 'false'}">${t.name}</option>`;
                            });

                            const card = `
                            <div class="card" style="border: 1px solid var(--border-color); border-left: 5px solid var(--accent-color); border-radius: 10px; padding: 20px; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">
                                    <span style="font-weight:700; color:var(--primary-color); display:flex; align-items:center; gap:8px;">
                                        <i data-lucide="file-text" style="width:18px;"></i> ${file.name}
                                    </span>
                                    <span style="font-size:0.75rem; color:#64748b; background:#f1f5f9; padding:2px 8px; border-radius:20px;">${(file.size/1024/1024).toFixed(2)} MB</span>
                                </div>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                                    <div>
                                        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:5px;">Belge Başlığı *</label>
                                        <input type="text" name="documents[${index}][title]" class="form-control" required value="${file.name.split('.').slice(0,-1).join('.')}" style="width:100%; padding:10px; border:1px solid var(--border-color); border-radius:6px;">
                                    </div>
                                    <div>
                                        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:5px;">Doküman Tipi *</label>
                                        <select name="documents[${index}][document_type_id]" class="form-control doc-type-selector" data-index="${index}" required style="width:100%; padding:10px; border:1px solid var(--border-color); border-radius:6px;">
                                            ${options}
                                        </select>
                                    </div>
                                </div>
                                <div id="custom-fields-${index}" style="margin-top:15px; display:none; padding:15px; border:1px dashed #cbd5e1; border-radius:8px; background:#f8fafc;"></div>
                                <div style="margin-top: 15px;">
                                    <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:5px;">Bitiş Tarihi</label>
                                    <input type="date" name="documents[${index}][expire_at]" id="expire-${index}" class="form-control" style="width:100%; max-width:200px; padding:10px; border:1px solid var(--border-color); border-radius:6px;">
                                </div>
                            </div>`;
                            cardsContainer.insertAdjacentHTML('beforeend', card);
                        });
                        lucide.createIcons();

                        // Custom Fields AJAX...
                        document.querySelectorAll('.doc-type-selector').forEach(sel => {
                            sel.addEventListener('change', function() {
                                const idx = this.dataset.index;
                                const cont = document.getElementById(
                                    `custom-fields-${idx}`);
                                const exp = document.getElementById(`expire-${idx}`);
                                const reqExp = this.options[this.selectedIndex].dataset
                                    .req - exp === 'true';

                                exp.required = reqExp;
                                exp.style.borderColor = reqExp ? '#ef4444' :
                                    'var(--border-color)';

                                if (!this.value) {
                                    cont.style.display = 'none';
                                    return;
                                }
                                cont.style.display = 'block';
                                cont.innerHTML =
                                    '<i data-lucide="loader" class="spin" style="width:14px;"></i> Yükleniyor...';

                                fetch(`{{ url('/api/document-types') }}/${this.value}/fields`, {
                                        headers: {
                                            'Accept': 'application/json'
                                        }
                                    })
                                    .then(r => r.json()).then(fields => {
                                        if (!fields || fields.length === 0) {
                                            cont.style.display = 'none';
                                            return;
                                        }
                                        let html =
                                            '<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">';
                                        fields.forEach(f => {
                                            const req = f.required ?
                                                'required' : '';
                                            html +=
                                                `<div><label style="font-size:0.8rem; font-weight: 600;">${f.label}</label><input type="${f.type||'text'}" name="documents[${idx}][metadata][${f.name}]" class="form-control" style="width:100%; padding:8px; border:1px solid var(--border-color); border-radius:4px;" ${req}></div>`;
                                        });
                                        cont.innerHTML = html + '</div>';
                                    });
                            });
                        });
                    }
                });
            }

            {{-- ONAY SÜRECİ JS (ORİJİNAL) --}}
            const workflowContainer = document.getElementById('workflow-container');
            const addApproverBtn = document.getElementById('add-approver-btn');
            const template = document.getElementById('approver-row-template').innerHTML;
            let approverIdx = 0;

            addApproverBtn.addEventListener('click', () => {
                const emptyMsg = document.getElementById('empty-workflow-msg');
                if (emptyMsg) emptyMsg.style.display = 'none';

                const row = template.replace(/__INDEX__/g, approverIdx);
                const $row = $(row);
                $(workflowContainer).append($row);
                $row.find('.approver-select').select2({
                    width: '100%'
                });
                approverIdx++;
                lucide.createIcons();
            });

            workflowContainer.addEventListener('click', e => {
                const btn = e.target.closest('.remove-approver');
                if (btn) {
                    btn.closest('.workflow-row').remove();
                    if (workflowContainer.querySelectorAll('.workflow-row').length === 0) {
                        document.getElementById('empty-workflow-msg').style.display = 'block';
                    }
                }
            });
            const privacySelect = document.getElementById('privacyLevelSelect');
            const descBox = document.getElementById('privacy-desc-box');

            // Gizlilik seviyeleri için açıklama sözlüğü (Dinamik yapına uygun)
            const privacyDescriptions = {
                'public': 'Şirketteki <strong>herkes</strong> görebilir. Belgenin hangi departman klasöründe olduğu fark etmez.',
                'confidential': 'Sadece <strong>bu klasörün ait olduğu departmanda çalışanlar</strong> görebilir. Diğer departmanlara kapalıdır.',
                'strictly_confidential': 'Kendi departmanınızın klasöründe olsa bile, sadece <strong>özel yetki verilmiş kişiler</strong> görebilir.',
                'board_only': 'Sadece <strong>Yönetim Kurulu</strong> üyeleri görebilir. Şirketteki başka hiç kimse erişemez.'
            };

            if (privacySelect) {
                privacySelect.addEventListener('change', function() {
                    const selected = this.value;

                    if (selected) {
                        descBox.style.display = 'block';

                        // Eğer sözlükte varsa özel açıklamayı yaz, yoksa (panelden yeni eklediğin bir şeyse) varsayılan metni göster
                        const explanation = privacyDescriptions[selected] ||
                            'Bu gizlilik seviyesine atanan özel yetkilere sahip kişiler dışında kimse bu belgeyi göremez.';

                        descBox.innerHTML =
                            `<i data-lucide="shield-check" style="width:16px; vertical-align:middle; margin-right:4px;"></i> ${explanation}`;
                        lucide.createIcons(); // İkonun render edilmesi için
                    } else {
                        descBox.style.display = 'none';
                    }
                });
            }
        });
    </script>
@endpush
