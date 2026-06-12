@extends('layouts.app')

@section('content')
    <div class="page-header mb-20">
        <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm btn-outline-secondary mb-10"
            style="display: inline-flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" style="width: 14px;"></i> {{ __('Detaya Dön') }}
        </a>
        <h1 class="page-title" style="font-size: 1.8rem; color: var(--primary-color);">✏️ {{ __('Süreci Düzenle:') }}
            TASK-{{ str_pad($task->id, 4, '0', STR_PAD_LEFT) }}</h1>
    </div>

    @include('partials.alerts')

    <form action="{{ route('tasks.update', $task->id) }}" method="POST" id="taskEditForm">
        @csrf @method('PUT')

        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 25px; align-items: start;">

            {{-- ========================================== --}}
            {{-- SOL SÜTUN: FORM VE DİNAMİK ALANLAR         --}}
            {{-- ========================================== --}}
            <div class="card glass-card"
                style="border-radius: 12px; padding: 25px; border-top: 4px solid var(--accent-color);">

                {{-- Şablon Bilgisi (Sadece Okunabilir) --}}
                <div class="form-group mb-20">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">{{ __('Süreç Şablonu') }}</label>
                    <div
                        style="background: #f1f5f9; border: 1px solid var(--border-color); padding: 12px; border-radius: 8px; color: var(--text-muted); font-weight: 500; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="layout-template" style="width: 18px;"></i> {{ $task->template->name }}
                    </div>
                    <small class="text-muted" style="display: block; margin-top: 5px;">Şablon türü sonradan
                        değiştirilemez.</small>
                </div>

                <div class="form-group mb-20">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">{{ __('Görev Başlığı / Konusu') }}
                        <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $task->title) }}"
                        required
                        style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); font-size: 1.05rem;">
                </div>

                {{-- DİNAMİK ALANLAR (Pre-fill yapılmış halde) --}}
                @if (empty($task->template->fields))
                    <div
                        style="text-align: center; padding: 20px; color: var(--text-muted); font-style: italic; background: #f8fafc; border-radius: 8px; border: 1px dashed var(--border-color);">
                        Bu şablona ait dinamik bir form alanı bulunmuyor.
                    </div>
                @else
                    <div
                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 25px; border-top: 1px solid var(--border-color); padding-top: 25px;">
                        @foreach ($task->template->fields as $field)
                            @php
                                $val = old("custom_data.{$field['name']}", $task->custom_data[$field['name']] ?? '');
                                $required = !empty($field['required']) ? 'required' : '';
                                $reqStar = !empty($field['required']) ? '<span class="text-danger">*</span>' : '';
                                $colSpan = $field['type'] === 'textarea' ? 'grid-column: 1 / -1;' : '';
                            @endphp

                            <div class="form-group" style="{{ $colSpan }}">
                                <label
                                    style="font-size:0.85rem; font-weight:600; color:var(--text-color); margin-bottom:6px; display:block;">{!! $field['label'] !!}
                                    {!! $reqStar !!}</label>

                                @if ($field['type'] === 'textarea')
                                    <textarea name="custom_data[{{ $field['name'] }}]" class="form-control"
                                        placeholder="{{ $field['placeholder'] ?? '' }}" {{ $required }}
                                        style="width:100%; padding:10px; border-radius:6px; border:1px solid var(--border-color); min-height:80px; resize:vertical;">{{ $val }}</textarea>
                                @elseif($field['type'] === 'date')
                                    <input type="date" name="custom_data[{{ $field['name'] }}]" class="form-control"
                                        value="{{ $val }}" {{ $required }}
                                        style="width:100%; padding:10px; border-radius:6px; border:1px solid var(--border-color);">
                                @elseif($field['type'] === 'select')
                                    {{-- YENİ EKLENEN: AÇILIR MENÜ (DROPDOWN) --}}
                                    <select name="custom_data[{{ $field['name'] }}]" class="form-control"
                                        {{ $required }}
                                        style="width:100%; padding:10px; border-radius:6px; border:1px solid var(--border-color);">
                                        <option value="">{{ __('-- Seçiniz --') }}</option>
                                        @if (isset($field['options']) && is_array($field['options']))
                                            @foreach ($field['options'] as $option)
                                                {{-- Eğer önceden seçilmiş bir değer varsa (value), onu selected yapıyoruz --}}
                                                <option value="{{ $option }}"
                                                    {{ $val == $option ? 'selected' : '' }}>
                                                    {{ $option }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                @else
                                    <input type="{{ $field['type'] ?? 'text' }}" name="custom_data[{{ $field['name'] }}]"
                                        class="form-control" value="{{ $val }}"
                                        placeholder="{{ $field['placeholder'] ?? '' }}" {{ $required }}
                                        style="width:100%; padding:10px; border-radius:6px; border:1px solid var(--border-color);">
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ========================================== --}}
            {{-- SAĞ SÜTUN: EKİP VE KURMAY YÖNETİMİ         --}}
            {{-- ========================================== --}}
            <div>

                {{-- SIKIMOD KİLİT UYARISI --}}
                @if (!$task->template->allow_ad_hoc_members)
                    <div class="alert alert-danger mb-20"
                        style="background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 15px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; line-height: 1.4;">
                        <div style="display: flex; gap: 8px;">
                            <i data-lucide="lock" style="width: 20px; flex-shrink: 0;"></i>
                            <span>{{ __('Bu süreç Sıkı Mod ile korunmaktadır. Dışarıdan ekstra personel daveti kapatılmıştır.') }}</span>
                        </div>
                    </div>
                @endif

                <div class="card glass-card"
                    style="border-radius: 12px; padding: 25px; border-top: 4px solid #f59e0b; position: sticky; top: 20px;">

                    {{-- 1. ZORUNLU GRUP (Varsa Göster) --}}
                    @if ($task->template->mandatoryGroup && $task->template->mandatoryGroup->members->count() > 0)
                        <div style="margin-bottom: 25px;">
                            <h5
                                style="margin: 0 0 10px 0; font-size: 0.85rem; color: var(--danger-color); display: flex; align-items: center; gap: 6px; text-transform: uppercase;">
                                <i data-lucide="shield-check" style="width: 16px;"></i> <span>🛡️ ZORUNLU KADRO:
                                    {{ $task->template->mandatoryGroup->name }}</span>
                            </h5>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                @foreach ($task->template->mandatoryGroup->members as $member)
                                    @php $isManager = $member->pivot->role === 'manager'; @endphp
                                    <div
                                        style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 10px 12px; display: flex; align-items: center; justify-content: space-between; opacity: 0.95;">
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <div
                                                style="width: 28px; height: 28px; border-radius: 50%; background: {{ $isManager ? '#eab308' : '#64748b' }}; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: bold;">
                                                {{ strtoupper(substr($member->name, 0, 2)) }}
                                            </div>
                                            <div style="line-height: 1.2;">
                                                <div
                                                    style="font-weight: 600; font-size: 0.85rem; color: var(--text-color);">
                                                    {{ $member->name }}</div>
                                                <div style="font-size: 0.7rem; color: var(--text-muted);">
                                                    {{ $member->department->name ?? '' }}</div>
                                            </div>
                                        </div>
                                        <div>
                                            <span
                                                style="font-size: 0.65rem; background: {{ $isManager ? '#fef3c7' : '#f1f5f9' }}; color: {{ $isManager ? '#b45309' : '#475569' }}; padding: 3px 6px; border-radius: 4px; font-weight: 700; border: 1px solid {{ $isManager ? '#fcd34d' : '#e2e8f0' }};">
                                                <i data-lucide="lock" style="width:10px; vertical-align:middle;"></i>
                                                {{ $isManager ? 'YÖNETİCİ' : 'ÜYE' }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- 2. AD-HOC EKİP BÖLÜMÜ (Sıkı Mod Değilse Göster) --}}
                    @if ($task->template->allow_ad_hoc_members)
                        <div>
                            <div class="flex-between mb-15"
                                style="display: flex; justify-content: space-between; align-items: center;">
                                <h4
                                    style="margin: 0; color: var(--secondary-color); display:flex; align-items:center; gap:8px;">
                                    <i data-lucide="users" style="width: 20px; color: #f59e0b;"></i>
                                    {{ __('Proje Ekibi (Ad-Hoc)') }}
                                </h4>
                                <button type="button" id="addTeamMemberBtn" class="btn btn-sm btn-outline-warning"
                                    style="font-weight: 600; color: #d97706; border-color: #fcd34d; background: #fffbeb;">
                                    <i data-lucide="plus" style="width: 14px;"></i> Kişi Ekle
                                </button>
                            </div>

                            <p class="text-muted" style="font-size: 0.8rem; margin-bottom: 20px; line-height: 1.4;">Bu işi
                                yürütürken size yardımcı olacak veya kapanışta onay verecek kişileri buradan
                                atayabilirsiniz.</p>

                            {{-- TomSelect Gizli Input Alanı --}}
                            <div id="teamSelectorWrapper"
                                style="display: none; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px dashed var(--border-color); margin-bottom: 15px;">
                                <div class="form-group mb-10">
                                    <label
                                        style="font-size: 0.8rem; font-weight: 600; margin-bottom: 4px; display: block;">Personel
                                        Seçin</label>
                                    <select id="userSelectBox" placeholder="İsim veya departman ara..."></select>
                                </div>
                                <div class="form-group mb-10">
                                    <label
                                        style="font-size: 0.8rem; font-weight: 600; margin-bottom: 4px; display: block;">Rolü</label>
                                    <select id="roleSelectBox" class="form-control"
                                        style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--border-color);">
                                        <option value="member">👤 Sadece Üye (Görüntüleyici/Yorumcu)</option>
                                        <option value="manager">👑 Proje Yöneticisi (Kapanış Onaycısı)</option>
                                    </select>
                                </div>
                                <div style="text-align: right;">
                                    <button type="button" id="confirmAddMemberBtn" class="btn btn-sm btn-success"
                                        style="font-weight: 600;">Listeye Ekle</button>
                                </div>
                            </div>

                            {{-- Seçilen/Mevcut Kişilerin Listesi --}}
                            <div id="teamMembersContainer"
                                style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px;">
                            </div>
                        </div>
                    @endif

                    <div style="border-top: 1px solid var(--border-color); padding-top: 20px;">
                        <button type="submit" class="btn btn-primary btn-block"
                            style="width: 100%; padding: 15px; font-size: 1.1rem; font-weight: bold;">
                            <i data-lucide="save" style="width: 18px; margin-right: 5px;"></i>
                            {{ __('Güncellemeleri Kaydet') }}
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();

            @if ($task->template->allow_ad_hoc_members)
                @php
                    // Blade motorunu yormamak için PHP mapping işlemini burada yapıyoruz
                    $mappedUsers = $users->map(function ($u) {
                        return [
                            'id' => $u->id,
                            'name' => $u->name,
                            'department' => $u->department->name ?? 'Birim Yok',
                        ];
                    });

                    $mappedAdHocMembers = $existingAdHocMembers->map(function ($u) {
                        return [
                            'id' => $u->id,
                            'name' => $u->name,
                            'department' => $u->department->name ?? '',
                            'role' => $u->pivot->role,
                        ];
                    });
                @endphp

                // Laravel'den gelen tertemiz dizileri JS objelerine çeviriyoruz
                const usersData = @json($mappedUsers);
                const existingAdHocMembers = @json($mappedAdHocMembers);
                const mandatoryUserIds = @json($mandatoryUserIds);
                const creatorId = {{ $task->creator_id }};

                const container = document.getElementById('teamMembersContainer');
                let memberIndex = 0;

                // TomSelect'i Başlat
                let tsInstance = new TomSelect('#userSelectBox', {
                    options: usersData,
                    valueField: 'id',
                    labelField: 'name',
                    searchField: ['name', 'department'],
                    render: {
                        option: function(item, escape) {
                            return `<div><span style="font-weight:600;">${escape(item.name)}</span> <span style="font-size:0.75rem; color:#64748b;">(${escape(item.department)})</span></div>`;
                        },
                        item: function(item, escape) {
                            return `<div>${escape(item.name)}</div>`;
                        }
                    }
                });

                // Zorunlu üyeleri ve Kurucuyu Seçim Listesinden (TomSelect) Engelle
                mandatoryUserIds.forEach(id => tsInstance.disableOption(id));
                tsInstance.disableOption(creatorId);

                // Halihazırda var olan Ad-Hoc üyeleri sayfaya çiz
                existingAdHocMembers.forEach(member => {
                    renderMemberHTML(member.id, member.name, member.department, member.role);
                    tsInstance.disableOption(member.id); // Çizileni de listeden kilitle
                });

                // "Kişi Ekle" Butonuna Tıklanınca TomSelect Kutusunu Aç
                document.getElementById('addTeamMemberBtn').addEventListener('click', function() {
                    const wrapper = document.getElementById('teamSelectorWrapper');
                    wrapper.style.display = wrapper.style.display === 'none' ? 'block' : 'none';
                });

                // Listeye Ekle Butonu
                document.getElementById('confirmAddMemberBtn').addEventListener('click', function() {
                    const userId = tsInstance.getValue();
                    if (!userId) {
                        alert("Lütfen bir personel seçin.");
                        return;
                    }

                    const userObj = tsInstance.options[userId];
                    const role = document.getElementById('roleSelectBox').value;

                    renderMemberHTML(userId, userObj.name, userObj.department, role);

                    tsInstance.disableOption(userId);
                    tsInstance.clear();
                    document.getElementById('teamSelectorWrapper').style.display = 'none';
                });

                // HTML Çizici Fonksiyon
                function renderMemberHTML(userId, userName, userDept, role) {
                    const isManager = role === 'manager';
                    const html = `
                    <div class="team-member-row" style="background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <input type="hidden" name="team_members[${memberIndex}][user_id]" value="${userId}">
                        <input type="hidden" name="team_members[${memberIndex}][role]" value="${role}">
                        
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="width: 32px; height: 32px; border-radius: 50%; background: ${isManager ? '#eab308' : '#3b82f6'}; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold;">
                                ${userName.substring(0,2).toUpperCase()}
                            </div>
                            <div style="line-height: 1.2;">
                                <div style="font-weight: 600; font-size: 0.9rem; color: var(--text-color);">${userName}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">${userDept}</div>
                            </div>
                        </div>

                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="font-size: 0.7rem; background: ${isManager ? '#fffbeb' : '#f1f5f9'}; color: ${isManager ? '#b45309' : '#475569'}; padding: 4px 8px; border-radius: 4px; font-weight: 700; border: 1px solid ${isManager ? '#fde68a' : '#e2e8f0'};">
                                ${isManager ? '👑 YÖNETİCİ' : 'ÜYE'}
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-member-btn" data-userid="${userId}" style="padding: 4px 8px; border:none; background:#fee2e2;">
                                <i data-lucide="x" style="width: 14px;"></i>
                            </button>
                        </div>
                    </div>
                `;
                    container.insertAdjacentHTML('beforeend', html);
                    lucide.createIcons();
                    memberIndex++;
                }

                // Silme İşlemi (Delegate)
                container.addEventListener('click', function(e) {
                    const removeBtn = e.target.closest('.remove-member-btn');
                    if (removeBtn) {
                        const userId = removeBtn.getAttribute('data-userid');
                        tsInstance.enableOption(userId); // TomSelect'te tekrar seçilebilir yap
                        removeBtn.closest('.team-member-row').remove();
                    }
                });
            @endif
        });
    </script>
@endpush
