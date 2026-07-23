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
            background-color: #f0f9ff;
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

    /* YENİ: Mod Seçici Buton Stilleri */
    .mode-selector {
        display: flex;
        background: #f1f5f9;
        border-radius: 10px;
        padding: 5px;
        margin-bottom: 25px;
    }

    .mode-btn {
        flex: 1;
        padding: 12px;
        text-align: center;
        border: none;
        background: transparent;
        border-radius: 8px;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .mode-btn.active {
        background: #fff;
        color: var(--primary-color);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
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
                {{ __('Belge Yükleme ve Form Doldurma') }}</h1>
            <p class="text-muted" style="margin: 0;">
                {{ __('Fiziksel bir dosya yükleyebilir veya sistem üzerinden form doldurarak resmi kurumsal PDF üretebilirsiniz.') }}
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

        {{-- YENİ: Backend'e form tabanlı olduğunu bildirecek gizli input --}}
        <input type="hidden" name="is_form_based_submission" id="isFormBasedInput" value="0">

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

                    {{-- YENİ: MOD SEÇİCİ --}}
                    <div class="mode-selector">
                        <button type="button" class="mode-btn active" data-mode="0">
                            <i data-lucide="file-up" style="width: 18px;"></i> Fiziksel Dosya Yükle
                        </button>
                        <button type="button" class="mode-btn" data-mode="1">
                            <i data-lucide="form-input" style="width: 18px;"></i> Form Doldur
                        </button>
                    </div>

                    {{-- BÖLÜM A: FİZİKSEL DOSYA SEÇİCİ (Orijinal alanınız) --}}
                    <div id="physical-upload-section" class="form-group" style="margin-bottom: 30px;">
                        <label class="form-label"
                            style="font-weight: 600; color: var(--secondary-color); margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            {{ __('Dosyaları Yükle') }} <span class="text-danger">*</span>
                        </label>
                        <div class="file-upload-wrapper" style="position: relative; width: 100%;">
                            <input type="file" name="files[]" id="file" class="file-upload-input" multiple
                                style="position: absolute; margin: 0; padding: 0; width: 100%; height: 100%; outline: none; opacity: 0; cursor: pointer; z-index: 2;">
                            <label for="file" class="file-upload-label"
                                style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 20px; background-color: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; color: var(--text-muted); transition: all 0.3s ease; z-index: 1;">
                                <i data-lucide="upload-cloud"
                                    style="width: 48px; height: 48px; color: var(--accent-color); margin-bottom: 15px; opacity: 0.8;"></i>
                                <span id="file-name-display"
                                    style="font-size: 1.1rem; text-align: center;">{{ __('Dosyaları Seçin veya Buraya Sürükleyin') }}</span>
                                <span
                                    style="font-size: 0.85rem; color: #94a3b8; margin-top: 8px; text-align:center;">{{ __('Toplam Limit: 40MB | Her belge için ayrı bir veri kartı oluşturulacaktır. Toplu seçim için CTRL tuşuna basılı tutun VEYA dosyaları tek tek seçip listeye ekleyin.') }}</span>
                            </label>
                        </div>
                    </div>

                    {{-- BÖLÜM B: AKILLI FORM SEÇİCİ (YENİ ALAN) --}}
                    <div id="smart-form-section" class="form-group"
                        style="display: none; margin-bottom: 30px; text-align: center; padding: 30px; background: #f8fafc; border: 1px dashed var(--border-color); border-radius: 12px;">
                        <i data-lucide="file-signature"
                            style="width: 48px; height: 48px; color: var(--primary-color); margin-bottom: 15px; opacity: 0.6; display: block; margin: 0 auto 15px auto;"></i>
                        <h4 style="margin-bottom: 10px; color: var(--primary-color);">Form Belge Üretimi</h4>
                        <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 20px;">Fiziksel dosya yüklemenize gerek
                            yok. Formu doldurduğunuzda sistem kurumsal antetli bir PDF oluşturacaktır.</p>
                        <button type="button" id="add-virtual-card-btn" class="btn btn-primary"
                            style="padding: 10px 20px; display: inline-flex; align-items: center; gap: 8px;">
                            <i data-lucide="plus-circle" style="width: 18px;"></i> Yeni Form Belgesi Ekle
                        </button>
                    </div>

                    {{-- DİNAMİK KARTLAR (Her dosya/form için oluşacak) --}}
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
                                    <option value="{{ $id }}"
                                        {{ old('folder_id', request('folder_id')) == $id ? 'selected' : '' }}>
                                        {!! $indent !!}{{ $folderName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 600;">
                                {{ __('Gizlilik Seviyesi') }} <span class="text-danger">*</span>
                            </label>
                            <select name="privacy_level" id="privacyLevelSelect" class="form-control" required
                                style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                                <option value="">{{ __('-- Gizlilik Seviyesi Seçin --') }}</option>
                                @foreach ($privacyLevels as $key => $label)
                                    <option value="{{ $key }}">{{ __($label) }}</option>
                                @endforeach
                            </select>
                            <div id="privacy-desc-box"
                                style="display: none; margin-top: 8px; padding: 12px; border-radius: 6px; background-color: #f0fdf4; border-left: 4px solid #22c55e; font-size: 0.85rem; color: #166534;">
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label" style="font-weight: 600; display: flex; align-items: center; gap: 6px;">
                            {{ __('Bilgi Verilecek Kullanıcılar') }} <i data-lucide="bell-ring"
                                style="width: 15px; color: var(--accent-color);"></i>
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
                    {{-- <div class="form-group"
                        style="margin-bottom: 20px; border-top: 1px dashed #e2e8f0; padding-top: 15px;">
                        <label class="form-label" style="font-weight: 600; display: flex; align-items: center; gap: 6px;">
                            {{ __('Sistem Dışı Bilgilendirme (Harici E-Posta)') }}
                            <i data-lucide="mail-plus" style="width: 15px; color: #ce1126;"></i>
                        </label>
                        <select name="external_emails[]" id="externalEmailsSelect" class="form-control"
                            multiple="multiple" style="width: 100%;">
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label" style="font-weight: 600; display: flex; align-items: center; gap: 6px;">
                            {{ __('Dış Paydaş Link Son Geçerlilik Tarihi') }}
                            <i data-lucide="calendar-clock" style="width: 15px; color: var(--accent-color);"></i>
                        </label>
                        <input type="datetime-local" name="external_expires_at" class="form-control"
                            min="{{ now()->format('Y-m-d\TH:i') }}" style="max-width: 300px;">
                        <small class="text-muted" style="font-size: 11px; display: block; margin-top: 4px;">
                            Belgeye dışarıdan erişecek kişilerin bağlantısının ne zaman kesileceğini seçiniz.
                            <span style="color: #ce1126; font-weight: 600;">Boş bırakırsanız, şirket politikası aksi
                                belirtilmedikçe süresiz aktif kalır.</span>
                        </small>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label" style="font-weight: 600; display: flex; align-items: center; gap: 6px;">
                            {{ __('Not') }}
                            <i data-lucide="file-text" style="width: 15px; color: #718096;"></i>
                        </label>
                        <textarea name="external_note" class="form-control" rows="3" style="resize: none;"
                            placeholder="Harici kullanıcılara gidecek e-posta içerisine eklenecek özel not yazabilirsiniz..."></textarea>
                    </div> --}}


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
                        <i data-lucide="rocket" style="width: 20px;"></i> {{ __('Yükle / Üret') }}
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

            // Backend'den gelen tüm doküman tipleri (JSON Formatında)
            const docTypes = @json($documentTypes);

            // YENİ: Küresel Kart Sayacı ve Sanal Dosya Taşıyıcısı
            let globalCardIndex = 0;
            const dataTransfer = new DataTransfer();

            // YENİ: MOD DEĞİŞTİRME MANTIĞI
            const modeBtns = document.querySelectorAll('.mode-btn');
            const physicalSection = document.getElementById('physical-upload-section');
            const smartFormSection = document.getElementById('smart-form-section');
            const isFormBasedInput = document.getElementById('isFormBasedInput');

            modeBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Kullanıcı mod değiştirirse mevcut seçili verileri temizle uyarısı
                    if (cardsContainer.innerHTML.trim() !== "") {
                        if (!confirm(
                                "Yükleme modunu değiştirirseniz eklediğiniz belgeler sıfırlanacaktır. Devam etmek istiyor musunuz?"
                            )) return;
                    }

                    modeBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    const mode = this.getAttribute('data-mode');
                    isFormBasedInput.value = mode;

                    // Formu sıfırla
                    cardsContainer.innerHTML = '';
                    dataTransfer.items.clear();
                    fileInput.value = '';
                    fileNameDisplay.textContent = 'Dosyaları Seçin veya Buraya Sürükleyin';
                    globalCardIndex = 0;
                    fileInput.required = (mode === "0"); // Sadece fiziksel ise input zorunlu

                    if (mode === "0") {
                        physicalSection.style.display = 'block';
                        smartFormSection.style.display = 'none';
                    } else {
                        physicalSection.style.display = 'none';
                        smartFormSection.style.display = 'block';
                    }
                });
            });

            // KART ÜRETME MOTORU (Hem Fiziksel Hem Sanal Dosyalar İçin Ortak)
            function createDocumentCard(fileData = null) {
                const isFormMode = (isFormBasedInput.value === "1");
                const currentIdx = globalCardIndex++;

                // Başlık ve Boyut Varsayımları
                const defaultTitle = fileData ? fileData.name.split('.').slice(0, -1).join('.') :
                    `Form Belgesi ${currentIdx + 1}`;
                const displaySize = fileData ? (fileData.size / 1024 / 1024).toFixed(2) + ' MB' :
                    '<i data-lucide="zap" style="width:14px; margin-right:4px;"></i> Smart Form';
                const displayName = fileData ? fileData.name : `Akıllı Döküman Şablonu`;

                // Sadece İlgili Moda Uygun Doküman Tiplerini Filtrele
                let options = '<option value="">-- Tip Seçin --</option>';
                docTypes.forEach(t => {
                    const isTypeFormBased = t.is_form_based ? 1 : 0;
                    // Moda göre filtrele (Form modundaysak sadece form destekleyenleri göster)
                    if (isTypeFormBased == isFormBasedInput.value) {
                        options +=
                            `<option value="${t.id}" data-req-exp="${t.requires_expiration_date ? 'true' : 'false'}">${t.name}</option>`;
                    }
                });

                const card = `
                <div class="card" id="doc-card-${currentIdx}" style="border: 1px solid var(--border-color); border-left: 5px solid ${isFormMode ? '#8b5cf6' : 'var(--accent-color)'}; border-radius: 10px; padding: 20px; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.02); position:relative;">
                    ${isFormMode ? `<button type="button" class="btn btn-sm btn-outline-danger remove-virtual-card" data-target="doc-card-${currentIdx}" style="position:absolute; top:15px; right:15px;"><i data-lucide="x" style="width:14px;"></i></button>` : ''}
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:10px; padding-right: ${isFormMode ? '35px' : '0'};">
                        <span style="font-weight:700; color:var(--primary-color); display:flex; align-items:center; gap:8px;">
                            <i data-lucide="${isFormMode ? 'form-input' : 'file-text'}" style="width:18px;"></i> ${displayName}
                        </span>
                        <span style="font-size:0.75rem; color:#64748b; background:#f1f5f9; padding:2px 8px; border-radius:20px; display:flex; align-items:center;">
                            ${displaySize}
                        </span>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                        <div>
                            <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:5px;">Belge Başlığı (Çıktı Adı) *</label>
                            <input type="text" name="documents[${currentIdx}][title]" class="form-control" required value="${defaultTitle}" style="width:100%; padding:10px; border:1px solid var(--border-color); border-radius:6px;">
                        </div>
                        <div>
                            <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:5px;">Doküman/Form Tipi*</label>
                            <select name="documents[${currentIdx}][document_type_id]" class="form-control doc-type-selector" data-index="${currentIdx}" required style="width:100%; padding:10px; border:1px solid var(--border-color); border-radius:6px;">
                                ${options}
                            </select>
                        </div>
                    </div>
                    <div id="custom-fields-${currentIdx}" style="margin-top:15px; display:none; padding:15px; border:1px dashed #cbd5e1; border-radius:8px; background:#f8fafc;"></div>
                    <div style="margin-top: 15px;">
                        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:5px;">Geçerlilik Bitiş Tarihi</label>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <input type="date" name="documents[${currentIdx}][expire_at]" id="expire-${currentIdx}" class="form-control expire-input" style="width:100%; max-width:200px; padding:10px; border:1px solid var(--border-color); border-radius:6px; transition: all 0.3s;">
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.85rem; color: var(--text-muted); font-weight: 500; user-select: none;">
                                <input type="checkbox" name="documents[${currentIdx}][is_indefinite]" value="1" class="is-indefinite-checkbox" data-index="${currentIdx}" style="width: 16px; height: 16px; accent-color: var(--primary-color);">
                                <i data-lucide="infinity" style="width: 16px; color: var(--primary-color);"></i> Süresiz (Tarih Yok)
                            </label>
                        </div>
                    </div> 
                    <div>
                                <label style="font-size:0.85rem; font-weight:600; display:flex; align-items:center; gap:5px; margin-bottom:5px;">
                                    <i data-lucide="info" style="width:14px; color:var(--text-muted);"></i> Geçerlilik Özel Durum / Açıklama
                                    <span style="font-size:0.7rem; color:#94a3b8; font-weight:normal;">(Opsiyonel)</span>
                                </label>
                                <textarea name="documents[${currentIdx}][validity_description]" class="form-control" rows="2" placeholder="Örn: Sözleşme 1 yıllıktır ancak taraflarca feshedilmediği sürece otomatik uzar..." style="width:100%; padding:10px; border:1px solid var(--border-color); border-radius:6px; resize:vertical; font-size:0.85rem; transition: border-color 0.3s;"></textarea>
                    </div>
                </div>`;
                cardsContainer.insertAdjacentHTML('beforeend', card);
                lucide.createIcons();
                attachDocTypeListener(currentIdx);
            }

            // SANAL KART EKLEME BUTONU DİNLEYİCİSİ
            document.getElementById('add-virtual-card-btn').addEventListener('click', () => {
                createDocumentCard(null);
            });

            // SANAL KART SİLME BUTONU DİNLEYİCİSİ
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.remove-virtual-card');
                if (btn) {
                    const targetId = btn.getAttribute('data-target');
                    document.getElementById(targetId).remove();
                }
            });

            // FİZİKSEL DOSYA SEÇİM MOTORU (GÜNCELLENDİ)
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    let totalSize = 0;
                    Array.from(this.files).forEach(file => {
                        let exists = false;
                        for (let i = 0; i < dataTransfer.items.length; i++) {
                            if (dataTransfer.items[i].getAsFile().name === file.name) exists = true;
                        }
                        if (!exists) dataTransfer.items.add(file);
                    });

                    this.files = dataTransfer.files;
                    const allFiles = Array.from(this.files);
                    allFiles.forEach(file => totalSize += file.size);
                    const totalSizeMB = (totalSize / (1024 * 1024)).toFixed(2);

                    if (totalSize > 41943040) {
                        alertBox.style.display = 'flex';
                        alertText.innerHTML =
                            `<strong>Boyut Limiti Aşıldı!</strong> Toplam: <strong>${totalSizeMB} MB</strong> seçtiniz. Maksimum 40 MB yükleyebilirsiniz.`;
                        submitBtn.disabled = true;
                        cardsContainer.innerHTML = '';
                        dataTransfer.items.clear();
                        this.files = dataTransfer.files;
                        fileNameDisplay.textContent = 'Dosyalar silindi, lütfen tekrar seçin';
                    } else {
                        alertBox.style.display = 'none';
                        submitBtn.disabled = false;
                        cardsContainer.innerHTML = '';
                        globalCardIndex =
                            0; // Fizikselde her seçimde yeniden sıfırlayıp tam liste basıyoruz

                        if (allFiles.length > 0) {
                            fileNameDisplay.textContent = allFiles.length +
                                ' Dosya Eklendi (Daha fazla ekleyebilirsiniz)';
                            fileNameDisplay.style.color = 'var(--success-color)';
                        }
                        allFiles.forEach(file => {
                            createDocumentCard(file);
                        });
                    }
                });
            }

            // AJAX İLE CUSTOM FIELD ÇEKME FONKSİYONU
            function attachDocTypeListener(idx) {
                const sel = document.querySelector(`.doc-type-selector[data-index="${idx}"]`);
                if (!sel) return;

                sel.addEventListener('change', function() {
                    const cont = document.getElementById(`custom-fields-${idx}`);
                    const exp = document.getElementById(`expire-${idx}`);
                    const reqExp = this.options[this.selectedIndex].dataset.req === 'true';
                    const indefiniteCheckbox = document.querySelector(
                        `.is-indefinite-checkbox[data-index="${idx}"]`);

                    if (!indefiniteCheckbox.checked) {
                        exp.required = reqExp;
                        exp.style.borderColor = reqExp ? '#ef4444' : 'var(--border-color)';
                    } else {
                        exp.required = false;
                        exp.style.borderColor = 'var(--border-color)';
                    }

                    if (!this.value) {
                        cont.style.display = 'none';
                        return;
                    }
                    cont.style.display = 'block';
                    cont.innerHTML =
                        '<i data-lucide="loader" class="spin" style="width:14px;"></i> Form Alanları Yükleniyor...';
                    lucide.createIcons();

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
                                const req = f.required ? 'required' : '';
                                html +=
                                    `<div><label style="font-size:0.8rem; font-weight: 600;">${f.label} ${f.required ? '<span class="text-danger">*</span>' : ''}</label><input type="${f.type||'text'}" name="documents[${idx}][metadata][${f.name}]" class="form-control" style="width:100%; padding:8px; border:1px solid var(--border-color); border-radius:4px;" ${req}></div>`;
                            });
                            cont.innerHTML = html + '</div>';
                        });
                });
            }

            // ... Orijinal Onay Süreci Kodların (Değişmedi) ...
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
                        const explanation = privacyDescriptions[selected] ||
                            'Bu gizlilik seviyesine atanan özel yetkilere sahip kişiler dışında kimse bu belgeyi göremez.';
                        descBox.innerHTML =
                            `<i data-lucide="shield-check" style="width:16px; vertical-align:middle; margin-right:4px;"></i> ${explanation}`;
                        lucide.createIcons();
                    } else {
                        descBox.style.display = 'none';
                    }
                });
            }
            // if (typeof $.fn.select2 !== 'undefined') {
            //     $('#externalEmailsSelect').select2({
            //         tags: true,
            //         tokenSeparators: [',', ' '],
            //         placeholder: "ornek@firma.com şeklinde e-posta giriniz",
            //         allowClear: true,
            //         createTag: function(params) {
            //             // E-posta validasyon kontrolü (Yanlış girdileri tag yapmaz)
            //             var pattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
            //             if (pattern.test(params.term)) {
            //                 return {
            //                     id: params.term,
            //                     text: params.term,
            //                     isNew: true
            //                 };
            //             }
            //             return null;
            //         }
            //     });
            // }
        });

        document.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('is-indefinite-checkbox')) {
                const idx = e.target.getAttribute('data-index');
                const dateInput = document.getElementById(`expire-${idx}`);
                if (e.target.checked) {
                    dateInput.disabled = true;
                    dateInput.value = '';
                    dateInput.style.backgroundColor = '#f1f5f9';
                    dateInput.style.opacity = '0.6';
                    dateInput.required = false;
                } else {
                    dateInput.disabled = false;
                    dateInput.style.backgroundColor = '#fff';
                    dateInput.style.opacity = '1';
                    const typeSelect = document.querySelector(`.doc-type-selector[data-index="${idx}"]`);
                    if (typeSelect && typeSelect.value) {
                        const reqExp = typeSelect.options[typeSelect.selectedIndex].dataset.req === 'true';
                        dateInput.required = reqExp;
                    }
                }
            }
        });
    </script>
@endpush
