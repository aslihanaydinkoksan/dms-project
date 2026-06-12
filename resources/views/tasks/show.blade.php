@extends('layouts.app')

@section('content')
    <div class="page-header flex-between mb-20">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary"
                style="display: flex; align-items: center; gap: 6px;">
                <i data-lucide="arrow-left" style="width: 16px;"></i> {{ __('Geri') }}
            </a>
            <div>
                <h1 class="page-title"
                    style="font-size: 1.6rem; color: var(--primary-color); display: flex; align-items: center; gap: 10px; margin: 0;">
                    TASK-{{ str_pad($task->id, 4, '0', STR_PAD_LEFT) }}: {{ $task->title }}
                </h1>
                <p class="text-muted" style="font-size: 0.9rem; margin-top: 5px; margin-bottom: 0;">
                    <strong>{{ $task->template->name ?? '-' }}</strong> süreci | Başlatan:
                    <strong>{{ $task->creator->name ?? '-' }}</strong> | Tarih: {{ $task->created_at->format('d.m.Y H:i') }}

                    {{-- GÖREVİ BİTİREN KİŞİ BİLGİSİ --}}
                    @if ($task->status === 'completed')
                        <span style="color: var(--border-color); margin: 0 5px;">|</span>
                        <span style="color: #166534;"><i data-lucide="check-check"
                                style="width: 14px; vertical-align: middle;"></i> Kapatan: <strong>Proje
                                Yöneticisi</strong></span>
                    @endif
                </p>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: 10px;">
            {{-- YETKİ KONTROLÜ: Sadece Yetkililer ve Aktif Görevlerde Düzenle Butonu Çıksın --}}
            @php
                $canEditTask =
                    $task->status === 'active' &&
                    ($task->creator_id === auth()->id() ||
                        \Illuminate\Support\Facades\DB::table('task_user')
                            ->where('task_id', $task->id)
                            ->where('user_id', auth()->id())
                            ->where('role', 'manager')
                            ->exists() ||
                        auth()->user()->hasRole('Super Admin') ||
                        auth()->user()->hasRole('Admin'));
            @endphp

            @if ($canEditTask)
                {{-- margin-top: 10px kaldırıldı, kapsayıcıya taşındı --}}
                <a href="{{ route('tasks.edit', $task->id) }}" class="btn btn-sm btn-outline-primary"
                    style="display: inline-flex; align-items: center; gap: 6px; border-radius: 8px; font-weight: 600;">
                    <i data-lucide="edit" style="width: 16px;"></i> {{ __('Süreci Düzenle') }}
                </a>
            @endif

            @if ($task->status === 'completed')
                {{-- İkonların da yazıyla kusursuz hizalanması için rozetlere de display: inline-flex eklendi --}}
                <span class="badge"
                    style="display: inline-flex; align-items: center; gap: 6px; background: #dcfce7; color: #166534; padding: 8px 16px; border-radius: 8px; font-size: 0.9rem; border: 1px solid #bbf7d0;">
                    <i data-lucide="check-circle" style="width: 16px;"></i>
                    Tamamlandı / Arşivlendi
                </span>
            @elseif($task->status === 'pending_closure_approval')
                <span class="badge"
                    style="display: inline-flex; align-items: center; gap: 6px; background: #fffbeb; color: #b45309; padding: 8px 16px; border-radius: 8px; font-size: 0.9rem; border: 1px solid #fde68a;">
                    <i data-lucide="clock" style="width: 16px;"></i>
                    Yönetici Onayı Bekliyor
                </span>
            @elseif($task->status === 'active')
                <span class="badge"
                    style="display: inline-flex; align-items: center; gap: 6px; background: #eff6ff; color: #1e40af; padding: 8px 16px; border-radius: 8px; font-size: 0.9rem; border: 1px solid #bfdbfe;">
                    <i data-lucide="activity" style="width: 16px;"></i>
                    Devam Ediyor
                </span>
            @endif
        </div>
    </div>

    @include('partials.alerts')

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px; align-items: start;">

        {{-- SOL SÜTUN: DİNAMİK FORM VERİLERİ VE TİMELİNE --}}
        <div style="display: flex; flex-direction: column; gap: 25px;">

            <div class="card glass-card"
                style="border-radius: 12px; padding: 25px; border-top: 4px solid var(--accent-color);">
                <h4
                    style="margin: 0 0 20px 0; color: var(--secondary-color); display:flex; align-items:center; gap:8px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                    <i data-lucide="layout-list" style="width: 20px; color: var(--accent-color);"></i>
                    {{ __('Sürece Özel Detaylar (Form Verileri)') }}
                </h4>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    @if (is_array($task->template->fields) && is_array($task->custom_data))
                        @foreach ($task->template->fields as $field)
                            <div
                                style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; {{ ($field['type'] ?? '') === 'textarea' ? 'grid-column: 1 / -1;' : '' }}">
                                <label
                                    style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 5px;">
                                    {{ $field['label'] ?? 'İsimsiz Alan' }}
                                </label>
                                <div style="font-size: 1rem; color: var(--text-color); font-weight: 500;">
                                    @php
                                        $value = $task->custom_data[$field['name']] ?? '-';
                                    @endphp

                                    @if (empty($value))
                                        <span style="color: #94a3b8; font-style: italic;">Girilmemiş</span>
                                    @else
                                        {{ $value }}
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div
                            style="grid-column: 1 / -1; padding: 20px; text-align: center; color: var(--text-muted); font-style: italic;">
                            Bu sürece ait doldurulmuş herhangi bir özel form alanı bulunmuyor.
                        </div>
                    @endif
                </div>
            </div>

            {{-- ZAMAN ÇİZELGESİ (TIMELINE) --}}
            <div class="card glass-card"
                style="border-radius: 12px; padding: 25px; border-top: 4px solid var(--primary-color);">
                <h4 style="margin: 0 0 20px 0; color: var(--secondary-color); display:flex; align-items:center; gap:8px;">
                    <i data-lucide="git-commit" style="width: 20px; color: var(--primary-color);"></i>
                    {{ __('Süreç Geçmişi ve İzlenebilirlik') }}
                </h4>

                <div class="timeline-container"
                    style="position: relative; padding-left: 20px; border-left: 2px solid #e2e8f0; margin-left: 10px;">
                    {{-- Başlangıç --}}
                    <div style="position: relative; margin-bottom: 25px;">
                        <span
                            style="position: absolute; left: -27px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: var(--primary-color); border: 2px solid #fff; box-shadow: 0 0 0 2px var(--primary-color);"></span>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 4px;">
                            {{ $task->created_at->format('d.m.Y H:i') }}</div>
                        <div style="font-weight: 600; color: var(--text-color);">Süreç Başlatıldı</div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">{{ $task->creator->name ?? 'Sistem' }}
                            tarafından oluşturuldu.</div>
                    </div>

                    {{-- Onay Bekleme Aşaması --}}
                    @if ($task->status === 'pending_closure_approval' || $task->closure_note)
                        <div style="position: relative; margin-bottom: 25px;">
                            <span
                                style="position: absolute; left: -27px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: #f59e0b; border: 2px solid #fff; box-shadow: 0 0 0 2px #f59e0b;"></span>
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 4px;">
                                {{ $task->updated_at->format('d.m.Y H:i') }}</div>
                            <div style="font-weight: 600; color: var(--text-color);">Kapanış Onayına Sunuldu</div>
                            @if ($task->closure_note)
                                <div style="font-size: 0.85rem; color: var(--text-muted); font-style: italic;">
                                    "{{ \Illuminate\Support\Str::limit($task->closure_note, 50) }}"</div>
                            @endif
                        </div>
                    @endif

                    {{-- Bitiş Aşaması --}}
                    @if ($task->status === 'completed')
                        <div style="position: relative;">
                            <span
                                style="position: absolute; left: -27px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: #10b981; border: 2px solid #fff; box-shadow: 0 0 0 2px #10b981;"></span>
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 4px;">
                                {{ $task->updated_at->format('d.m.Y H:i') }}</div>
                            <div style="font-weight: 600; color: #166534;">Süreç Tamamlandı (Kapatıldı)</div>
                            <div style="font-size: 0.85rem; color: var(--text-muted);">Proje Yöneticisi onayı ile arşive
                                kaldırıldı.</div>
                        </div>
                    @else
                        <div style="position: relative;">
                            <span
                                style="position: absolute; left: -27px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: #cbd5e1; border: 2px solid #fff;"></span>
                            <div style="font-weight: 600; color: #94a3b8; font-style: italic;">Sürecin tamamlanması
                                bekleniyor...</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- SAĞ SÜTUN: AKSİYONLAR VE EKİP --}}
        <div style="display: flex; flex-direction: column; gap: 25px;">

            @php
                // YETKİ VE DURUM KONTROLLERİ
                $isManager =
                    $task->users
                        ->where('id', auth()->id())
                        ->where('pivot.role', 'manager')
                        ->isNotEmpty() || auth()->user()->hasRole('Super Admin');
                $isAssigned = $task->users->where('id', auth()->id())->isNotEmpty();

                $needsAction = false;
                if ($task->status === 'active' && $isAssigned) {
                    $needsAction = true; // Üye işi kapatabilir
                } elseif ($task->status === 'pending_closure_approval' && $isManager) {
                    $needsAction = true; // Yönetici onaylayabilir
                }
            @endphp

            {{-- YENİ: YANIP SÖNEN DİNAMİK AKSİYON KARTI (PULSING LIGHT) --}}
            @if ($needsAction)
                <div class="card glass-card action-required-card"
                    style="border-radius: 12px; padding: 25px; border: 2px solid var(--warning-color); background: #fffbeb;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                        <div class="pulsing-dot"></div>
                        <h4 style="margin: 0; color: #b45309; font-weight: 800;">{{ __('Sıra Sizde!') }}</h4>
                    </div>

                    @if ($task->status === 'active')
                        <p style="font-size: 0.85rem; color: #92400e; margin-bottom: 15px;">Bu süreci yürütmekle
                            görevlisiniz. İşlemleriniz bittiyse süreci kapatarak yönetici onayına sunabilirsiniz.</p>
                        <button type="button"
                            onclick="openClosureModal({{ $task->id }}, {{ $task->template->requires_document_on_closure ? 'true' : 'false' }})"
                            class="btn btn-warning"
                            style="width: 100%; border-radius: 8px; font-weight: 700; color: #78350f; box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.3);">
                            <i data-lucide="check-circle"
                                style="width: 18px; margin-right: 6px; vertical-align: text-bottom;"></i> İşi Kapat / Onaya
                            Sun
                        </button>
                    @elseif($task->status === 'pending_closure_approval')
                        <p style="font-size: 0.85rem; color: #92400e; margin-bottom: 15px;">Ekibiniz bu görevi tamamladı.
                            Yönetici olarak evrakları inceleyip nihai kararı vermeniz bekleniyor.</p>
                        <div style="display: flex; gap: 10px;">
                            <form action="{{ route('tasks.approve-closure', $task->id) }}" method="POST"
                                style="flex:1; margin:0;"
                                onsubmit="return confirm('Görevi kalıcı olarak kapatmak istediğinize emin misiniz?');">
                                @csrf
                                <button type="submit" class="btn btn-success"
                                    style="width: 100%; border-radius: 8px; font-weight: 700; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3);">
                                    <i data-lucide="check" style="width: 18px;"></i> Onayla
                                </button>
                            </form>
                            <form action="{{ route('tasks.reject-closure', $task->id) }}" method="POST"
                                style="flex:1; margin:0;">
                                @csrf
                                <button type="submit" class="btn btn-danger"
                                    style="width: 100%; border-radius: 8px; font-weight: 700;">
                                    <i data-lucide="x" style="width: 18px;"></i> Reddet
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @endif

            {{-- KAPANIŞ BİLGİLERİ KARTI --}}
            @if ($task->status === 'completed' || $task->closure_note)
                <div class="card glass-card" style="border-radius: 12px; padding: 25px; border-top: 4px solid #10b981;">
                    <h4
                        style="margin: 0 0 15px 0; color: var(--secondary-color); display:flex; align-items:center; gap:8px;">
                        <i data-lucide="check-square" style="width: 20px; color: #10b981;"></i>
                        {{ __('Kapanış Detayları') }}
                    </h4>

                    @if ($task->closure_note)
                        <div
                            style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                            <span
                                style="display: block; font-size: 0.8rem; font-weight: 700; color: #166534; margin-bottom: 5px;">AÇIKLAMA
                                NOTU</span>
                            <div style="font-size: 0.95rem; color: #15803d; line-height: 1.5; font-style: italic;">
                                "{{ $task->closure_note }}"
                            </div>
                        </div>
                    @endif

                    @if ($task->closure_document_path)
                        <div>
                            <span
                                style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 5px;">EKLİ
                                KANIT (BELGE)</span>
                            <a href="{{ route('tasks.closure-document', $task->id) }}" target="_blank"
                                class="btn btn-outline-success"
                                style="width: 100%; display: flex; justify-content: center; align-items: center; gap: 8px; padding: 10px; font-weight: 600;">
                                <i data-lucide="download-cloud" style="width: 18px;"></i> İndir / Görüntüle
                            </a>
                        </div>
                    @endif
                </div>
                {{-- ======================================================= --}}
                {{-- TAMAMLANMIŞ GÖREV ROZETİ VE YENİDEN AÇMA BUTONU         --}}
                {{-- ======================================================= --}}
                @if ($task->isCompleted())
                    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap; margin-top: 10px;">

                        {{-- Mevcut Arşiv Rozeti --}}
                        <span class="badge"
                            style="background: #dcfce7; color: #166534; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; border: 1px solid #bbf7d0; display:flex; align-items:center; gap:5px;">
                            <i data-lucide="check-circle" style="width: 16px;"></i> {{ __('Tamamlandı / Arşivlendi') }}
                        </span>

                        {{-- YÖNETİCİLER İÇİN "RE-OPEN" BUTONU --}}
                        @php
                            // Projenin yöneticisi mi kontrolü
                            $currentUserIsManager =
                                \Illuminate\Support\Facades\DB::table('task_user')
                                    ->where('task_id', $task->id)
                                    ->where('user_id', auth()->id())
                                    ->where('role', 'manager')
                                    ->exists() ||
                                auth()->user()->hasRole('Super Admin') ||
                                auth()->user()->hasRole('Admin');
                        @endphp

                        @if ($currentUserIsManager)
                            <form action="{{ route('tasks.reopen', $task->id) }}" method="POST"
                                onsubmit="return confirm('Bu süreci arşivden çıkarıp tekrar aktif Kanban tahtasına almak istediğinize emin misiniz?');"
                                style="margin: 0;">
                                @csrf
                                <button type="submit" class="btn btn-sm"
                                    style="background: #fffbeb; color: #d97706; border: 1px solid #fcd34d; font-weight: 600; display: flex; align-items: center; gap: 6px; border-radius: 8px; padding: 6px 12px; transition: all 0.2s; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.05);"
                                    onmouseover="this.style.transform='translateY(-2px)'"
                                    onmouseout="this.style.transform='translateY(0)'">
                                    <i data-lucide="refresh-cw" style="width: 14px;"></i>
                                    {{ __('Süreci Yeniden Aç (Re-Open)') }}
                                </button>
                            </form>
                        @endif

                    </div>
                @endif
            @endif

            {{-- PROJE EKİBİ KARTI --}}
            <div class="card glass-card" style="border-radius: 12px; padding: 25px; border-top: 4px solid #f59e0b;">
                <h4 style="margin: 0 0 15px 0; color: var(--secondary-color); display:flex; align-items:center; gap:8px;">
                    <i data-lucide="users" style="width: 20px; color: #f59e0b;"></i> {{ __('Proje Ekibi & Kurmaylar') }}
                </h4>

                @php
                    // Şablona ait zorunlu grup üyelerinin ID'lerini hızlı kontrol için bir diziye (Array) alıyoruz
$mandatoryUserIds = [];
if ($task->template->mandatoryGroup) {
    $mandatoryUserIds = $task->template->mandatoryGroup->members->pluck('id')->toArray();
                    }
                @endphp

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    @forelse($task->users as $user)
                        @php
                            $isManager = $user->pivot->role === 'manager';
                            $isCore = in_array($user->id, $mandatoryUserIds); // Bu kişi zorunlu gruptan mı geldi?
                        @endphp
                        <div
                            style="display: flex; align-items: center; justify-content: space-between; padding: 10px 15px; background: {{ $isCore ? '#f8fafc' : '#fff' }}; border: 1px solid {{ $isCore ? '#cbd5e1' : 'var(--border-color)' }}; border-radius: 8px;">

                            <div
                                style="font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                                <div
                                    style="width: 8px; height: 8px; border-radius: 50%; background: {{ $isManager ? '#eab308' : '#3b82f6' }};">
                                </div>
                                {{ $user->name }}
                            </div>

                            <div style="display: flex; align-items: center; gap: 8px;">
                                @if ($isCore)
                                    <span
                                        style="font-size: 0.65rem; background: #eef2ff; color: #4f46e5; padding: 4px 8px; border-radius: 4px; font-weight: 700; border: 1px solid #c7d2fe; display:flex; align-items:center; gap:3px;"
                                        title="Sistem tarafından otomatik atanmış zorunlu üye">
                                        <i data-lucide="shield-check" style="width:12px;"></i> ÇEKİRDEK KADRO
                                    </span>
                                @endif

                                @if ($isManager)
                                    <span
                                        style="font-size: 0.75rem; background: #fffbeb; color: #b45309; padding: 4px 8px; border-radius: 4px; font-weight: 600; border: 1px solid #fde68a;">👑
                                        YÖNETİCİ</span>
                                @else
                                    <span
                                        style="font-size: 0.75rem; background: #f1f5f9; color: #475569; padding: 4px 8px; border-radius: 4px; font-weight: 600;">ÜYE</span>
                                @endif
                            </div>

                        </div>
                    @empty
                        <div class="text-muted text-center" style="font-size: 0.85rem; padding: 10px;">Bu işe atanmış özel
                            bir ekip bulunmuyor.</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

    {{-- KAPANIŞ MODALI (Aynı sayfada butonların çalışması için gizli modal) --}}
    @if ($task->status === 'active')
        <div id="taskClosureModal" class="modal-overlay"
            style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(4px);">
            <div class="modal-content"
                style="background: #fff; width: 100%; max-width: 500px; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden;">
                <div class="modal-header"
                    style="padding: 20px 25px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f0fdf4;">
                    <h2
                        style="margin: 0; font-size: 1.25rem; display: flex; align-items: center; gap: 8px; color: #166534;">
                        <i data-lucide="check-circle-2" style="color: #10b981;"></i> {{ __('İşi Kapat / Onaya Sun') }}
                    </h2>
                    <button type="button" onclick="closeClosureModal()"
                        style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #166534;">&times;</button>
                </div>

                <form id="closureForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body" style="padding: 25px;">
                        <div class="alert alert-info mb-20"
                            style="font-size: 0.85rem; padding: 12px; background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; border-radius: 8px;">
                            <i data-lucide="info" style="width: 16px; vertical-align: text-bottom;"></i> Bu işlemi
                            yaptığınızda görev Kanban tahtasında kilitlenir ve projenin <strong>Yönetici (Manager)</strong>
                            rolündeki kişilerin nihai onayı beklenir.
                        </div>

                        <div class="form-group mb-20">
                            <label
                                style="display: block; font-weight: 600; margin-bottom: 8px;">{{ __('Kapanış Açıklaması / Son Notlar') }}</label>
                            <textarea name="closure_note" class="form-control" rows="3" placeholder="Yönetici için açıklama yazın..."
                                style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color);"></textarea>
                        </div>

                        <div class="form-group" id="documentUploadGroup">
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">
                                {{ __('Kanıt Dosyası / Evrak (PDF, Resim vb.)') }}
                                <span id="docRequiredStar" class="text-danger" style="display: none;">* (Zorunlu)</span>
                            </label>
                            <input type="file" name="closure_document" id="closureDocumentInput" class="form-control"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.html"
                                style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); background: #f8fafc;">
                        </div>
                    </div>

                    <div class="modal-footer"
                        style="padding: 15px 25px; border-top: 1px solid var(--border-color); background: #f8fafc; display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-outline-secondary" onclick="closeClosureModal()"
                            style="padding: 10px 20px;">İptal</button>
                        <button type="submit" class="btn btn-success"
                            style="padding: 10px 20px; display: flex; align-items: center; gap: 8px; font-weight: 600;">
                            <i data-lucide="send" style="width: 16px;"></i> Yöneticinin Onayına Gönder
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
    {{-- ======================================================= --}}
    {{-- FAZ 6.1: DİJİTAL AYAK İZİ (ZAMAN ÇİZELGESİ / TIMELINE) --}}
    {{-- ======================================================= --}}
    <div class="card glass-card mt-30"
        style="border-radius: 12px; padding: 25px; border-top: 4px solid var(--danger-color); margin-top: 30px;">
        <div class="flex-between mb-20">
            <h4 style="margin: 0; color: var(--secondary-color); display:flex; align-items:center; gap:8px;">
                <i data-lucide="fingerprint" style="width: 22px; color: var(--danger-color);"></i>
                {{ __('Süreç Denetim İzi (Audit Log)') }}
            </h4>
            <span class="badge"
                style="background: #fef2f2; color: #991b1b; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; border: 1px solid #fecaca;">
                <i data-lucide="shield-check" style="width: 14px; vertical-align: text-bottom;"></i> Sistem Tarafından
                Doğrulanmış Kayıtlar
            </span>
        </div>

        <div class="audit-timeline"
            style="position: relative; padding-left: 20px; margin-top: 20px; border-left: 2px solid var(--border-color);">

            @forelse($logs as $log)
                @php
                    // Log tipine göre ikon ve renk belirliyoruz
                    $icon = 'activity';
                    $color = '#64748b';
                    $bgColor = '#f1f5f9';
                    if ($log->action === 'stage_changed') {
                        $icon = 'arrow-right-left';
                        $color = '#3b82f6';
                        $bgColor = '#eff6ff';
                    }
                    if ($log->action === 'status_changed') {
                        $icon = 'refresh-cw';
                        $color = '#10b981';
                        $bgColor = '#f0fdf4';
                    }
                    if ($log->action === 'notification_sent') {
                        $icon = 'mail-check';
                        $color = '#d97706';
                        $bgColor = '#fffbeb';
                    }
                @endphp

                <div class="timeline-item" style="position: relative; margin-bottom: 25px; padding-left: 25px;">
                    {{-- Sol Çizgideki Yuvarlak İkon --}}
                    <div class="timeline-icon"
                        style="position: absolute; left: -18px; top: 0; width: 34px; height: 34px; border-radius: 50%; background: {{ $bgColor }}; border: 2px solid {{ $color }}; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <i data-lucide="{{ $icon }}" style="width: 16px; color: {{ $color }};"></i>
                    </div>

                    {{-- Log İçeriği --}}
                    <div
                        style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 15px; position: relative;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; flex-wrap: wrap; gap: 10px;">
                            <div style="font-size: 0.95rem; color: var(--text-color); font-weight: 500; line-height: 1.5;">
                                {!! $log->description !!}
                            </div>
                            <div
                                style="font-size: 0.75rem; color: var(--text-muted); white-space: nowrap; font-family: monospace; background: #e2e8f0; padding: 2px 6px; border-radius: 4px;">
                                {{ $log->created_at->format('d.m.Y H:i:s') }}
                            </div>
                        </div>

                        <div
                            style="display: flex; align-items: center; gap: 15px; font-size: 0.75rem; color: var(--text-muted); border-top: 1px dashed #cbd5e1; padding-top: 8px; margin-top: 8px;">
                            <span style="display: flex; align-items: center; gap: 4px;">
                                <i data-lucide="user" style="width: 14px;"></i> İşlemi Yapan:
                                <strong>{{ $log->user->name ?? 'Sistem Motoru' }}</strong>
                            </span>
                            <span style="display: flex; align-items: center; gap: 4px;">
                                <i data-lucide="globe" style="width: 14px;"></i> IP:
                                {{ $log->ip_address ?? 'Sistem İçi (Internal)' }}
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div
                    style="padding: 20px; text-align: center; color: var(--text-muted); font-style: italic; background: #f8fafc; border-radius: 8px; border: 1px dashed var(--border-color);">
                    Bu sürece ait henüz bir denetim izi (log) bulunmuyor.
                </div>
            @endforelse

            {{-- Başlangıç (Oluşturulma) Adımı --}}
            <div class="timeline-item" style="position: relative; padding-left: 25px;">
                <div class="timeline-icon"
                    style="position: absolute; left: -18px; top: 0; width: 34px; height: 34px; border-radius: 50%; background: #f3e8ff; border: 2px solid #a855f7; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="rocket" style="width: 16px; color: #a855f7;"></i>
                </div>
                <div style="background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 8px; padding: 15px;">
                    <div style="font-size: 0.95rem; color: #6b21a8; font-weight: 600; margin-bottom: 5px;">Süreç sistemde
                        başarıyla oluşturuldu ve başlatıldı.</div>
                    <div style="font-size: 0.75rem; color: #9333ea;">{{ $task->created_at->format('d.m.Y H:i:s') }} -
                        Başlatan: {{ $task->creator->name ?? 'Bilinmiyor' }}</div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <style>
        /* YANIP SÖNEN KART ANİMASYONU */
        @keyframes pulse-glow {
            0% {
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(245, 158, 11, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
            }
        }

        .action-required-card {
            animation: pulse-glow 2s infinite;
        }

        /* YANIP SÖNEN NOKTA (LED) */
        @keyframes blink {
            0% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.5;
                transform: scale(1.2);
            }

            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .pulsing-dot {
            width: 12px;
            height: 12px;
            background-color: #ef4444;
            border-radius: 50%;
            animation: blink 1s infinite ease-in-out;
            box-shadow: 0 0 8px #ef4444;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });

        // MODAL FONKSİYONLARI
        function openClosureModal(taskId, requiresDocument) {
            const modal = document.getElementById('taskClosureModal');
            if (!modal) return;
            const form = document.getElementById('closureForm');
            const docInput = document.getElementById('closureDocumentInput');
            const docStar = document.getElementById('docRequiredStar');

            form.action = `/tasks/${taskId}/request-closure`;

            if (requiresDocument) {
                docInput.required = true;
                docStar.style.display = 'inline';
            } else {
                docInput.required = false;
                docStar.style.display = 'none';
            }

            modal.style.display = 'flex';
        }

        function closeClosureModal() {
            const modal = document.getElementById('taskClosureModal');
            if (modal) {
                document.getElementById('closureForm').reset();
                modal.style.display = 'none';
            }
        }

        window.addEventListener('click', function(e) {
            const modal = document.getElementById('taskClosureModal');
            if (e.target === modal) {
                closeClosureModal();
            }
        });
    </script>
@endpush
