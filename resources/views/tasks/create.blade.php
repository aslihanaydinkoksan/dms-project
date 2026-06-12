@extends('layouts.app')

@section('content')
    <div class="page-header mb-20">
        <h1 class="page-title" style="font-size: 1.8rem; color: var(--primary-color);">🚀 {{ __('Yeni Süreç Başlat') }}
        </h1>
        <p class="text-muted" style="margin-top: 5px; font-size: 0.95rem;">
            {{ __('Sistemdeki hazır şablonları kullanarak yeni bir iş başlatın ve ekibinizi kurun.') }}</p>
    </div>

    @include('partials.alerts')

    <form action="{{ route('tasks.store') }}" method="POST" id="taskCreateForm" class="modern-form">
        @csrf

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px; align-items: start;">

            {{-- SOL SÜTUN: GÖREV DETAYLARI VE DİNAMİK FORM --}}
            <div>
                {{-- 1. ŞABLON VE TEMEL BİLGİLER --}}
                <div class="card glass-card mb-25"
                    style="border-radius: 12px; padding: 25px; border-top: 4px solid var(--accent-color);">
                    <h4
                        style="margin: 0 0 20px 0; color: var(--secondary-color); display:flex; align-items:center; gap:8px;">
                        <i data-lucide="info" style="width: 20px; color: var(--accent-color);"></i>
                        {{ __('Temel Bilgiler') }}
                    </h4>

                    <div class="form-group mb-20">
                        <label
                            style="font-weight: 600; margin-bottom: 8px; display: block;">{{ __('Süreç Şablonu (Kategori)') }}
                            <span class="text-danger">*</span></label>
                        <select name="process_template_id" id="templateSelector" class="form-control" required
                            style="width: 100%; padding: 12px; border-radius: 8px; font-weight: 500; font-size: 1rem; border: 2px solid var(--border-color);">
                            <option value="">{{ __('-- Lütfen Başlatacağınız Süreci Seçin --') }}</option>
                            @foreach ($templates as $template)
                                <option value="{{ $template->id }}"
                                    {{ isset($selectedTemplate) && $selectedTemplate->id == $template->id ? 'selected' : '' }}>
                                    📂 {{ $template->name }} ({{ $template->department->name ?? 'Genel' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" id="titleGroup" style="display: none; animation: fadeIn 0.3s ease;">
                        <label
                            style="font-weight: 600; margin-bottom: 8px; display: block;">{{ __('İşin / Görevin Başlığı') }}
                            <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control"
                            placeholder="Örn: 34 ABC 123 Plakalı Araç Kazası Bildirimi" required
                            style="width: 100%; padding: 12px; border-radius: 8px;">
                    </div>
                </div>

                {{-- 2. DİNAMİK FORM (NO-CODE EAV) ALANI --}}
                <div id="dynamicFormCard" class="card glass-card"
                    style="display: none; border-radius: 12px; padding: 25px; border-top: 4px solid #10b981; animation: fadeIn 0.4s ease;">
                    <h4
                        style="margin: 0 0 10px 0; color: var(--secondary-color); display:flex; align-items:center; gap:8px;">
                        <i data-lucide="layers" style="width: 20px; color: #10b981;"></i> {{ __('Sürece Özel Detaylar') }}
                    </h4>
                    <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 20px;">Lütfen seçtiğiniz şablonun
                        gerektirdiği aşağıdaki bilgileri eksiksiz doldurun.</p>

                    <div id="dynamicFieldsContainer" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    </div>
                </div>
            </div>

            {{-- SAĞ SÜTUN: AD-HOC PROJE EKİBİ (TOM SELECT) --}}

            {{-- SIKIMOD KİLİT UYARISI (JS İLE TETİKLENECEK) --}}
            <div id="strictModeLockBadge" class="alert alert-danger"
                style="display: none; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 12px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; margin-bottom: 15px;">
                <i data-lucide="lock" style="width: 16px; vertical-align: text-bottom; margin-right: 4px;"></i>
                {{ __('🔒 Bu süreç Sıkı Mod ile korunmaktadır. Sadece sistem tarafından atanan yetkili kadro bu süreci görebilir ve yönetebilir, dışarıdan ekstra personel daveti kapatılmıştır.') }}
            </div>

            <div class="card glass-card"
                style="border-radius: 12px; padding: 25px; border-top: 4px solid #f59e0b; position: sticky; top: 20px;">

                {{-- ZORUNLU (KİLİTLİ) GRUP BÖLÜMÜ (JS İle Doldurulacak) --}}
                <div id="mandatoryGroupSection" style="display: none; margin-bottom: 25px;">
                    <h5
                        style="margin: 0 0 10px 0; font-size: 0.85rem; color: var(--danger-color); display: flex; align-items: center; gap: 6px; text-transform: uppercase;">
                        <i data-lucide="shield-check" style="width: 16px;"></i> <span id="mandatoryGroupName">Zorunlu
                            Ekip</span>
                    </h5>
                    <div id="mandatoryMembersContainer" style="display: flex; flex-direction: column; gap: 8px;">
                    </div>
                </div>

                {{-- ========================================================== --}}
                {{-- İŞTE EKSİK OLAN VE EKLENEN KAPSAYICI (WRAPPER) BURASI    --}}
                {{-- ========================================================== --}}
                <div id="adhocTeamSelectorWrapper">
                    <div class="flex-between mb-15"
                        style="display: flex; justify-content: space-between; align-items: center;">
                        <h4 style="margin: 0; color: var(--secondary-color); display:flex; align-items:center; gap:8px;">
                            <i data-lucide="users" style="width: 20px; color: #f59e0b;"></i>
                            {{ __('Proje Ekibi (Ad-Hoc)') }}
                        </h4>
                        <button type="button" id="addTeamMemberBtn" class="btn btn-sm btn-outline-warning"
                            style="font-weight: 600; color: #d97706; border-color: #fcd34d; background: #fffbeb;">
                            <i data-lucide="plus" style="width: 14px;"></i> Kişi Ekle
                        </button>
                    </div>

                    <p class="text-muted" style="font-size: 0.8rem; margin-bottom: 20px; line-height: 1.4;">Bu işi
                        yürütürken
                        size yardımcı olacak veya kapanışta onay verecek kişileri buradan atayabilirsiniz.</p>

                    <div id="teamMembersContainer"
                        style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px;">
                    </div>
                </div>
                {{-- WRAPPER BİTİŞİ --}}

                {{-- SÜRECİ BAŞLAT BUTONU (Asla Gizlenmeyecek) --}}
                <div style="border-top: 1px solid var(--border-color); padding-top: 20px;">
                    <button type="submit" id="submitBtn" class="btn btn-primary btn-block" disabled
                        style="width: 100%; padding: 15px; font-size: 1.1rem; font-weight: bold; opacity: 0.5;">
                        <i data-lucide="rocket" style="width: 18px; margin-right: 5px;"></i> {{ __('Süreci Başlat') }}
                    </button>
                    <div class="text-center text-muted mt-2" style="font-size: 0.75rem; margin-top: 10px;">Lütfen önce bir
                        şablon seçin.</div>
                </div>
            </div>

        </div>
    </form>

    {{-- JS İÇİN TAKIM ÜYESİ ŞABLONU --}}
    <template id="team-member-template">
        <div class="team-member-row"
            style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; display: flex; gap: 10px; align-items: center; transition: all 0.2s;">
            <div style="flex: 2; min-width: 0;">
                <select name="team_members[__INDEX__][user_id]" class="user-search-select" required
                    placeholder="Personel ara...">
                    <option value="">Seçiniz...</option>
                </select>
            </div>
            <div style="flex: 1;">
                <select name="team_members[__INDEX__][role]" class="form-control role-select"
                    style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.85rem; cursor: pointer;">
                    <option value="member">👤 Üye (Member)</option>
                    <option value="manager">👑 Yönetici (Kapatma Onayı)</option>
                </select>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-outline-danger remove-member-btn"
                    style="padding: 6px 10px; border: none; background: #fee2e2;"><i data-lucide="x"
                        style="width: 14px;"></i></button>
            </div>
        </div>
    </template>
@endsection

@push('scripts')
    {{-- TomSelect Kütüphanesi --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Dinamik Çizilen Inputların Focus Efekti */
        .dynamic-input:focus {
            border-color: #10b981 !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
            outline: none;
        }

        /* TomSelect Tema Özelleştirmesi */
        .ts-control {
            border: 1px solid var(--border-color) !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
            box-shadow: none !important;
        }

        .ts-dropdown {
            border-radius: 6px !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
            border: 1px solid var(--border-color) !important;
        }

        .ts-dropdown .active {
            background-color: #f0f9ff !important;
            color: var(--primary-color) !important;
        }

        /* Yönetici (Manager) seçildiğinde Satır Efekti */
        .team-member-row.is-manager {
            background-color: #fffbeb !important;
            border-color: #fcd34d !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();

            const templateSelector = document.getElementById('templateSelector');
            const dynamicFormCard = document.getElementById('dynamicFormCard');
            const dynamicFieldsContainer = document.getElementById('dynamicFieldsContainer');
            const titleGroup = document.getElementById('titleGroup');
            const submitBtn = document.getElementById('submitBtn');

            // =========================================================================
            // 1. AJAX: DİNAMİK FORM ALANLARINI ÇİZME
            // =========================================================================
            templateSelector.addEventListener('change', function() {
                const templateId = this.value;
                dynamicFieldsContainer.innerHTML = '';

                if (!templateId) {
                    dynamicFormCard.style.display = 'none';
                    titleGroup.style.display = 'none';
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.5';
                    return;
                }

                // Loader Göster
                dynamicFormCard.style.display = 'block';
                titleGroup.style.display = 'block';
                dynamicFieldsContainer.innerHTML =
                    `<div style="grid-column: 1 / -1; text-align: center; padding: 30px; color: #10b981;"><i data-lucide="loader" class="spin" style="width:24px; animation: spin 1s linear infinite;"></i> Form şablonu yükleniyor...</div>`;
                lucide.createIcons();

                // AJAX İsteği
                fetch(`{{ url('/api/process-templates') }}/${templateId}/fields`)
                    .then(response => response.json())
                    .then(data => {
                        // Backend'den gelen yeni JSON yapısını ayrıştırıyoruz
                        const fields = data.fields || [];
                        const mandatoryGroup = data.mandatory_group;

                        dynamicFieldsContainer.innerHTML = '';
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';

                        // =================================================================
                        // 1. ZORUNLU KADRO & SIKIMOD (STRICT MODE) ÇİZİM MANTIĞI
                        // =================================================================
                        const mandatorySection = document.getElementById('mandatoryGroupSection');
                        const mandatoryContainer = document.getElementById('mandatoryMembersContainer');
                        const strictBadge = document.getElementById('strictModeLockBadge');
                        const adhocWrapper = document.getElementById('adhocTeamSelectorWrapper');

                        // Projedeki mevcut TomSelect nesnesini yakalıyoruz
                        const tomSelectControl = document.getElementById('teamMembers')?.tomselect;

                        if (mandatoryGroup && mandatoryGroup.members.length > 0) {
                            document.getElementById('mandatoryGroupName').innerText =
                                `🛡️ ZORUNLU KADRO: ${mandatoryGroup.name}`;
                            mandatoryContainer.innerHTML = '';

                            mandatoryGroup.members.forEach(member => {
                                const isManager = member.role === 'manager';
                                mandatoryContainer.insertAdjacentHTML('beforeend', `
                                    <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 10px 12px; display: flex; align-items: center; justify-content: space-between; opacity: 0.95;">
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <div style="width: 28px; height: 28px; border-radius: 50%; background: ${isManager ? '#eab308' : '#64748b'}; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: bold;">
                                                ${member.name.substring(0,2).toUpperCase()}
                                            </div>
                                            <div style="line-height: 1.2;">
                                                <div style="font-weight: 600; font-size: 0.85rem; color: var(--text-color);">${member.name}</div>
                                                <div style="font-size: 0.7rem; color: var(--text-muted);">${member.department}</div>
                                            </div>
                                        </div>
                                        <div>
                                            <span style="font-size: 0.65rem; background: ${isManager ? '#fef3c7' : '#f1f5f9'}; color: ${isManager ? '#b45309' : '#475569'}; padding: 3px 6px; border-radius: 4px; font-weight: 700; border: 1px solid ${isManager ? '#fcd34d' : '#e2e8f0'};">
                                                <i data-lucide="lock" style="width:10px; vertical-align:middle;"></i> ${isManager ? 'YÖNETİCİ' : 'ÜYE'}
                                            </span>
                                        </div>
                                    </div>
                                `);
                            });
                            mandatorySection.style.display = 'block';

                            // --- SIKIMOD VE MÜKERRERLİK İZOLASYON ALANI ---
                            if (!mandatoryGroup.allow_ad_hoc) {
                                // SIKIMOD AKTİF: Ad-Hoc seçim alanını kapat, kilit rozetini aç!
                                if (strictBadge) strictBadge.style.display = 'block';
                                if (adhocWrapper) adhocWrapper.style.display = 'none';
                                if (tomSelectControl) tomSelectControl
                                    .clear(); // Seçilmiş olanları sıfırla
                            } else {
                                // ESNEK MOD AKTİF: Seçim alanını aç, mükerrerliği önlemek için gruptakileri dropdown'dan kilitle!
                                if (strictBadge) strictBadge.style.display = 'none';
                                if (adhocWrapper) adhocWrapper.style.display = 'block';

                                if (tomSelectControl) {
                                    // Önce tüm seçeneklerin kilidini aç (şablon değiştirilme ihtimaline karşı)
                                    Object.keys(tomSelectControl.options).forEach(id => {
                                        tomSelectControl.enableOption(id);
                                    });
                                    // Zorunlu gruptan gelen kullanıcıların ID'lerini dropdown listesinden kaldır/disable yap!
                                    mandatoryGroup.member_ids.forEach(id => {
                                        tomSelectControl.disableOption(id);
                                        tomSelectControl.removeItem(
                                            id
                                        ); // Eğer kazara önceden seçilmişse listeden sök at
                                    });
                                }
                            }
                        } else {
                            // Şablonda zorunlu grup yoksa her şeyi normal/esnek moda geri çek
                            mandatorySection.style.display = 'none';
                            mandatoryContainer.innerHTML = '';
                            if (strictBadge) strictBadge.style.display = 'none';
                            if (adhocWrapper) adhocWrapper.style.display = 'block';

                            if (tomSelectControl) {
                                Object.keys(tomSelectControl.options).forEach(id => tomSelectControl
                                    .enableOption(id));
                            }
                        }

                        // =================================================================
                        // 2. DİNAMİK FORM ALANLARI (Senin Kodunun Birebir Aynısı)
                        // =================================================================
                        if (fields.length === 0) {
                            dynamicFieldsContainer.innerHTML =
                                `<div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: var(--text-muted); font-style: italic;">Bu şablona ait dinamik bir alan (soru) bulunmuyor. Görev başlığını yazıp ilerleyebilirsiniz.</div>`;
                        } else {
                            // JSON'dan gelen her field için Input çiz
                            fields.forEach(field => {
                                const isRequired = field.required ?
                                    '<span class="text-danger">*</span>' : '';
                                const requiredAttr = field.required ? 'required' : '';
                                let inputHtml = '';

                                // Tipine göre Input HTML oluştur
                                if (field.type === 'textarea') {
                                    inputHtml =
                                        `<textarea name="custom_data[${field.name}]" class="form-control dynamic-input" placeholder="${field.placeholder || ''}" ${requiredAttr} style="width:100%; padding:10px; border-radius:6px; border:1px solid var(--border-color); min-height:80px; resize:vertical;"></textarea>`;
                                } else if (field.type === 'date') {
                                    inputHtml =
                                        `<input type="date" name="custom_data[${field.name}]" class="form-control dynamic-input" ${requiredAttr} style="width:100%; padding:10px; border-radius:6px; border:1px solid var(--border-color);">`;
                                } else {
                                    inputHtml =
                                        `<input type="${field.type || 'text'}" name="custom_data[${field.name}]" class="form-control dynamic-input" placeholder="${field.placeholder || ''}" ${requiredAttr} style="width:100%; padding:10px; border-radius:6px; border:1px solid var(--border-color);">`;
                                }

                                // Satırı (Wrapper) oluştur ve ekle
                                const fieldHtml = `
                                    <div class="form-group" style="${field.type === 'textarea' ? 'grid-column: 1 / -1;' : ''}">
                                        <label style="font-size:0.85rem; font-weight:600; color:var(--text-color); margin-bottom:6px; display:block;">${field.label} ${isRequired}</label>
                                        ${inputHtml}
                                    </div>
                                `;
                                dynamicFieldsContainer.insertAdjacentHTML('beforeend',
                                    fieldHtml);
                            });
                        }

                        lucide.createIcons();
                    })
                    .catch(error => {
                        console.error("Şablon verisi çekilemedi:", error);
                        dynamicFieldsContainer.innerHTML =
                            `<div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #ef4444;"><i data-lucide="alert-triangle"></i> Form yüklenirken bir hata oluştu.</div>`;
                        lucide.createIcons();
                    });
            });

            // Eğer sayfaya template_id ile gelinmişse (Controller'dan selectedTemplate geldiyse) değişimi tetikle
            if (templateSelector.value) {
                templateSelector.dispatchEvent(new Event('change'));
            }

            // =========================================================================
            // 2. TOM SELECT İLE AD-HOC EKİP KURMA
            // =========================================================================
            const teamContainer = document.getElementById('teamMembersContainer');
            const teamTemplate = document.getElementById('team-member-template').innerHTML;
            let memberIndex = 0;

            document.getElementById('addTeamMemberBtn').addEventListener('click', function() {
                const html = teamTemplate.replace(/__INDEX__/g, memberIndex);
                teamContainer.insertAdjacentHTML('beforeend', html);

                const newRow = teamContainer.lastElementChild;
                const selectEl = newRow.querySelector('.user-search-select');
                const roleSelect = newRow.querySelector('.role-select');

                // Rol değiştiğinde satırın rengini değiştir (UX İyileştirmesi)
                roleSelect.addEventListener('change', function() {
                    if (this.value === 'manager') {
                        newRow.classList.add('is-manager');
                    } else {
                        newRow.classList.remove('is-manager');
                    }
                });

                // Tom Select'i Başlat (AJAX Destekli Arama)
                new TomSelect(selectEl, {
                    valueField: 'id',
                    labelField: 'name',
                    searchField: ['name', 'department'],
                    placeholder: 'Ekip arkadaşı ara...',
                    load: function(query, callback) {
                        if (!query.length) return callback();

                        fetch(`{{ url('/api/users/search') }}?q=${encodeURIComponent(query)}`)
                            .then(response => response.json())
                            .then(json => {
                                callback(json);
                            })
                            .catch(() => {
                                callback();
                            });
                    },
                    render: {
                        option: function(item, escape) {
                            return `<div>
                                <div style="font-weight:600;">${escape(item.name)}</div>
                                <div style="font-size:0.75rem; color:#64748b;">${escape(item.department)}</div>
                            </div>`;
                        },
                        item: function(item, escape) {
                            return `<div><span style="font-weight:600;">${escape(item.name)}</span> <span style="opacity:0.5;">(${escape(item.department)})</span></div>`;
                        }
                    }
                });

                lucide.createIcons();
                memberIndex++;
            });

            // Satır Silme İşlemi
            teamContainer.addEventListener('click', function(e) {
                const btn = e.target.closest('.remove-member-btn');
                if (btn) {
                    btn.closest('.team-member-row').remove();
                }
            });

        });
    </script>
@endpush
