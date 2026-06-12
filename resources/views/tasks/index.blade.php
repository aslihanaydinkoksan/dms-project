@extends('layouts.app')

@section('content')
    <div class="page-header flex-between mb-20" style="flex-wrap: wrap; gap: 20px;">
        <div>
            <h1 class="page-title"
                style="font-size: 1.8rem; color: var(--primary-color); display: flex; align-items: center; gap: 10px;">
                <div
                    style="background: #eff6ff; color: var(--accent-color); padding: 10px; border-radius: 12px; display:flex;">
                    <i data-lucide="{{ $currentView === 'calendar' ? 'calendar-days' : 'trello' }}"
                        style="width: 28px; height: 28px;"></i>
                </div>
                {{ __('Süreç Operasyon Merkezi') }}
            </h1>
            <p class="text-muted" style="font-size: 0.95rem; margin-top: 5px;">
                {{ __('Süreçlerinizi Kanban tahtasında yönetin veya Ajanda görünümünde tarihleri takip edin.') }}
            </p>
        </div>

        <div class="header-actions" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">

            {{-- VIEW TOGGLE (KANBAN vs CALENDAR) --}}
            <div class="view-toggle"
                style="display: flex; background: #e2e8f0; padding: 4px; border-radius: 8px; border: 1px solid var(--border-color);">
                <a href="{{ route('tasks.index', ['template_id' => $selectedTemplate->id ?? '', 'view' => 'kanban']) }}"
                    class="btn btn-sm"
                    style="border-radius: 6px; font-weight: 600; {{ $currentView === 'kanban' ? 'background: #fff; color: var(--primary-color); box-shadow: 0 2px 4px rgba(0,0,0,0.05);' : 'background: transparent; color: var(--text-muted);' }}">
                    <i data-lucide="layout-kanban" style="width: 16px; vertical-align: middle;"></i> Kanban
                </a>
                <a href="{{ route('tasks.index', ['template_id' => $selectedTemplate->id ?? '', 'view' => 'calendar']) }}"
                    class="btn btn-sm"
                    style="border-radius: 6px; font-weight: 600; {{ $currentView === 'calendar' ? 'background: #fff; color: var(--primary-color); box-shadow: 0 2px 4px rgba(0,0,0,0.05);' : 'background: transparent; color: var(--text-muted);' }}">
                    <i data-lucide="calendar" style="width: 16px; vertical-align: middle;"></i> Ajanda
                </a>
            </div>

            @if ($templates->isNotEmpty())
                <form method="GET" action="{{ route('tasks.index') }}" id="templateFilterForm"
                    style="margin: 0; display: flex; align-items: center; gap: 10px; background: var(--surface-color); padding: 6px 15px; border-radius: 8px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
                    <input type="hidden" name="view" value="{{ $currentView }}">
                    <label style="font-weight: 700; font-size: 0.85rem; color: var(--text-muted);"><i data-lucide="filter"
                            style="width:14px;"></i> Süreç:</label>
                    <select name="template_id" onchange="document.getElementById('templateFilterForm').submit();"
                        class="form-control form-control-sm"
                        style="font-weight: 600; color: var(--primary-color); border: none; background: transparent; cursor: pointer;">
                        @foreach ($templates as $tpl)
                            <option value="{{ $tpl->id }}"
                                {{ ($selectedTemplate->id ?? 0) == $tpl->id ? 'selected' : '' }}>{{ $tpl->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif

            <a href="{{ route('tasks.create') }}" class="btn btn-primary"
                style="display: flex; align-items: center; gap: 8px; font-weight: 600;">
                <i data-lucide="plus-circle" style="width: 18px;"></i> {{ __('Yeni İş Başlat') }}
            </a>
        </div>
    </div>

    @include('partials.alerts')

    {{-- EKRAN RENDER MANTIĞI --}}
    @if ($currentView === 'calendar')

        {{-- ======================================================== --}}
        {{-- AJANDA (FULLCALENDAR) GÖRÜNÜMÜ                           --}}
        {{-- ======================================================== --}}
        <div class="card glass-card"
            style="border-radius: 12px; padding: 20px; border-top: 4px solid var(--accent-color); min-height: 700px;">
            <div id="fullCalendarBoard"></div>
        </div>
    @else
        {{-- ======================================================== --}}
        {{-- KANBAN TAHTASI GÖRÜNÜMÜ VE KARTLAR                       --}}
        {{-- ======================================================== --}}

        @if ($selectedTemplate)
            <div
                style="background: rgba(248, 250, 252, 0.8); border: 1px solid var(--border-color); padding: 12px 20px; border-radius: 10px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between;">
                <span style="font-size: 0.95rem; font-weight: 600; color: var(--text-color);">
                    🚀 {{ __('Mevcut Süreç Ekranı:') }} <strong
                        style="color: var(--accent-color);">{{ $selectedTemplate->name }}</strong>
                    <span style="margin: 0 10px; color: #cbd5e1;">|</span>
                    🏢 {{ __('Bölüm:') }} <strong>{{ $selectedTemplate->department->name ?? 'Genel' }}</strong>
                </span>
                @if ($selectedTemplate->requires_document_on_closure)
                    <span class="badge"
                        style="background: #fee2e2; color: #991b1b; padding: 6px 12px; border-radius: 20px; font-weight: 700; font-size: 0.75rem; display: flex; align-items: center; gap: 4px;">
                        <i data-lucide="shield-alert" style="width:14px;"></i> {{ __('KAPANIŞTA EVRAK ZORUNLU') }}
                    </span>
                @endif
            </div>
        @endif

        <div class="kanban-board-wrapper custom-scrollbar"
            style="display: flex; gap: 20px; overflow-x: auto; padding-bottom: 20px; align-items: flex-start; min-height: calc(100vh - 280px); width: 100%;">
            @forelse($stages as $stage)
                <div class="kanban-column"
                    style="flex: 0 0 320px; background: rgba(241, 245, 249, 0.6); border: 1px solid var(--border-color); border-radius: 12px; display: flex; flex-direction: column; max-height: calc(100vh - 280px); box-shadow: var(--card-shadow);">
                    <div class="column-header"
                        style="padding: 15px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; border-top: 4px solid {{ $stage->color ?? '#3b82f6' }}; border-top-left-radius: 12px; border-top-right-radius: 12px; background: #fff;">
                        <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                            <span
                                style="width: 10px; height: 10px; border-radius: 50%; background-color: {{ $stage->color ?? '#3b82f6' }}; flex-shrink:0;"></span>
                            <h3 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--secondary-color);">
                                {{ $stage->name }}</h3>
                        </div>
                        <span class="card-count"
                            style="background: #e2e8f0; color: #475569; font-size: 0.75rem; font-weight: 700; padding: 3px 8px; border-radius: 20px;">{{ $stage->tasks->count() }}</span>
                    </div>

                    <div class="kanban-cards-container custom-scrollbar" data-stage-id="{{ $stage->id }}"
                        style="padding: 15px; display: flex; flex-direction: column; gap: 12px; overflow-y: auto; flex-grow: 1; min-height: 150px;">
                        @foreach ($stage->tasks as $task)
                            @php
                                // Modeldeki zekayı kullanarak kartın CSS sınıfını belirliyoruz
                                $statusClass = $task->isCompleted()
                                    ? 'card-status-completed'
                                    : ($task->isPendingApproval()
                                        ? 'card-status-pending'
                                        : 'card-status-active');
                            @endphp

                            <div class="card glass-card kanban-task-card {{ $statusClass }} {{ $task->isLocked() ? 'filtered' : '' }}"
                                data-task-id="{{ $task->id }}"
                                style="border-radius: 10px; padding: 15px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; transition: all 0.2s ease;">

                                {{-- 1. BAŞLIK VE ETİKETLER (BADGE) --}}
                                <div
                                    style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; display: flex; align-items: center; gap: 4px;">
                                    <i data-lucide="hash" style="width:12px; color: var(--accent-color);"></i>
                                    TASK-{{ str_pad($task->id, 4, '0', STR_PAD_LEFT) }}

                                    @if ($task->isCompleted())
                                        <span
                                            style="margin-left:auto; background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 0.65rem; border: 1px solid #bbf7d0; display:flex; align-items:center; gap:3px;">
                                            <i data-lucide="check-circle" style="width:12px;"></i> {{ __('ONAYLANDI') }}
                                        </span>
                                    @elseif ($task->isPendingApproval())
                                        <span
                                            style="margin-left:auto; background: #fffbeb; color: #b45309; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 0.65rem; border: 1px solid #fde68a;">
                                            {{ __('ONAYDA') }}
                                        </span>
                                    @endif
                                </div>

                                {{-- 2. GÖREV BAŞLIĞI --}}
                                <h5
                                    style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 700; color: {{ $task->isCompleted() ? '#166534' : 'var(--primary-color)' }}; line-height: 1.4;">
                                    {{ $task->title }}
                                </h5>

                                {{-- 3. OLUŞTURAN VE TARİH --}}
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; border-top: 1px dashed {{ $task->isCompleted() ? '#bbf7d0' : '#f1f5f9' }}; padding-top: 10px; font-size: 0.75rem; color: var(--text-muted);">
                                    <div style="display: flex; align-items: center; gap: 4px;" title="İşi Başlatan">
                                        <i data-lucide="user" style="width: 12px; color: var(--accent-color);"></i>
                                        <span
                                            style="font-weight: 500; max-width: 110px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $task->creator->name ?? 'Sistem' }}</span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <i data-lucide="calendar" style="width: 12px;"></i>
                                        <span>{{ $task->created_at->format('d.m.Y') }}</span>
                                    </div>
                                </div>

                                {{-- 4. AVATARLAR (Senin Kodunun Birebir Aynısı) --}}
                                @if ($task->users->isNotEmpty())
                                    <div class="task-avatars"
                                        style="display: flex; align-items: center; flex-wrap: wrap; gap: 4px; margin-top: 10px;">
                                        @foreach ($task->users as $user)
                                            @php
                                                $words = explode(' ', $user->name);
                                                $initials = strtoupper(
                                                    substr($words[0], 0, 1) .
                                                        (isset($words[1]) ? substr($words[1], 0, 1) : ''),
                                                );
                                                $isManager = $user->pivot->role === 'manager';
                                            @endphp
                                            <span class="avatar-circle"
                                                title="{{ $user->name }} ({{ $isManager ? 'Proje Lideri' : 'Ekip Üyesi' }})"
                                                style="width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 700; color: #fff; background-color: {{ $isManager ? '#eab308' : '#4f46e5' }}; border: 1px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); position: relative;">
                                                {{ $initials }}
                                                @if ($isManager)
                                                    <span
                                                        style="position: absolute; top: -5px; right: -3px; font-size: 8px; color: #eab308;">👑</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- 5. AKSİYON BUTONLARI --}}
                                <div
                                    style="margin-top: 15px; border-top: 1px solid {{ $task->isCompleted() ? '#bbf7d0' : 'var(--border-color)' }}; padding-top: 12px;">

                                    @if ($task->isCompleted())
                                        {{-- Kapatılmış ve Onaylanmış Görev --}}
                                        <div style="text-align: center;">
                                            <span
                                                style="font-size: 0.7rem; color: #15803d; font-weight: 600; display: block; margin-bottom: 5px;">Bu
                                                iş kapatıldı ve arşivlendi.</span>
                                            <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm btn-success"
                                                style="width: 100%; border-radius: 6px; font-weight: 600;">
                                                Arşiv Detayını İncele
                                            </a>
                                        </div>
                                    @elseif ($task->isPendingApproval())
                                        {{-- Onay Bekleyen Görev --}}
                                        <div style="margin-bottom: 10px;">
                                            <a href="{{ route('tasks.show', $task->id) }}"
                                                class="btn btn-sm btn-outline-secondary"
                                                style="width: 100%; border-radius: 6px; font-weight: 600; display: flex; justify-content: center; align-items: center; gap: 6px;">
                                                <i data-lucide="eye" style="width: 16px;"></i> Detaylara Bak
                                            </a>
                                        </div>
                                        <div
                                            style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px;">
                                            @if ($task->closure_note)
                                                <div
                                                    style="font-size: 0.75rem; color: #475569; margin-bottom: 8px; font-style: italic;">
                                                    "{{ $task->closure_note }}"</div>
                                            @endif
                                            @if ($task->closure_document_path)
                                                <a href="{{ route('tasks.closure-document', $task->id) }}"
                                                    target="_blank" class="btn btn-sm"
                                                    style="background: #eef2ff; color: #4f46e5; border: 1px solid #c7d2fe; font-size: 0.7rem; padding: 4px 8px; width: 100%; margin-bottom: 10px; display: block; text-align: center; border-radius: 4px;">
                                                    <i data-lucide="paperclip"
                                                        style="width:12px; vertical-align:middle;"></i>
                                                    {{ __('Ekli Kanıtı İncele') }}
                                                </a>
                                            @endif

                                            @php
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
                                                <div style="display: flex; gap: 6px;">
                                                    <form action="{{ route('tasks.approve-closure', $task->id) }}"
                                                        method="POST" style="flex:1; margin:0;"
                                                        onsubmit="return confirm('Kalıcı olarak kapatmak istediğinize emin misiniz?');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success"
                                                            style="width:100%; padding: 6px; font-size: 0.75rem; font-weight: 600; display:flex; justify-content:center; align-items:center; gap:4px;">
                                                            <i data-lucide="check" style="width:14px;"></i>
                                                            {{ __('Onayla') }}
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('tasks.reject-closure', $task->id) }}"
                                                        method="POST" style="flex:1; margin:0;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-danger"
                                                            style="width:100%; padding: 6px; font-size: 0.75rem; font-weight: 600; display:flex; justify-content:center; align-items:center; gap:4px;">
                                                            <i data-lucide="x" style="width:14px;"></i>
                                                            {{ __('Reddet') }}
                                                        </button>
                                                    </form>
                                                </div>
                                            @else
                                                <div class="text-center text-muted"
                                                    style="font-size: 0.7rem; padding-top: 5px;">
                                                    <i data-lucide="lock" style="width:10px; vertical-align:middle;"></i>
                                                    Sadece Proje Yöneticisi onaylayabilir.
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        {{-- Aktif Görev --}}
                                        <div style="margin-bottom: 10px;">
                                            <a href="{{ route('tasks.show', $task->id) }}"
                                                class="btn btn-sm btn-outline-secondary"
                                                style="width: 100%; border-radius: 6px; font-weight: 600; display: flex; justify-content: center; align-items: center; gap: 6px;">
                                                <i data-lucide="eye" style="width: 16px;"></i> Detaylara Bak
                                            </a>
                                        </div>
                                        <button type="button"
                                            onclick="openClosureModal({{ $task->id }}, {{ $task->template->requires_document_on_closure ? 'true' : 'false' }})"
                                            class="btn btn-sm btn-outline-success"
                                            style="width: 100%; border-radius: 6px; font-weight: 600; display: flex; justify-content: center; align-items: center; gap: 6px;">
                                            <i data-lucide="check-circle" style="width: 16px;"></i>
                                            {{ __('İşi Kapat / Onaya Sun') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="empty-state text-center"
                    style="background: var(--surface-color); border: 2px dashed var(--border-color); border-radius: 12px; padding: 60px 20px; width: 100%; max-width: 500px; margin: 0 auto;">
                    <div style="display: flex; justify-content: center; margin-bottom: 15px;"><i data-lucide="kanban"
                            style="width: 56px; height: 56px; color: var(--text-muted); opacity: 0.5;"></i></div>
                    <h3 style="color: var(--primary-color); margin-bottom: 10px;">{{ __('Kanban Aşamaları Eksik') }}</h3>
                    <p class="text-muted" style="font-size: 0.9rem; line-height: 1.5;">Bu süreç şablonu için henüz Kanban
                        sütunu tasarlanmamış.</p>
                </div>
            @endforelse
        </div>

        {{-- KAPANIS MODALI VE TOAST BİLDİRİMLERİ (SADECE KANBANDA GEREKLİ) --}}
        <div id="kanbanToast" class="custom-toast"></div>

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
@endsection

@push('scripts')

    {{-- SADECE KANBAN İÇİN GEREKLİ JS VE CSS --}}
    @if ($currentView === 'kanban')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
        <style>
            .sortable-ghost {
                opacity: 0.3 !important;
                background-color: #cbd5e1 !important;
                border: 2px dashed var(--primary-color) !important;
            }

            .sortable-chosen {
                transform: rotate(2deg);
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1) !important;
            }

            .custom-scrollbar::-webkit-scrollbar {
                width: 5px;
                height: 5px;
            }

            .custom-scrollbar::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 10px;
            }

            .custom-scrollbar::-webkit-scrollbar-track {
                background: transparent;
            }

            .kanban-task-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05) !important;
                border-color: #94a3b8 !important;
            }

            .custom-toast {
                position: fixed;
                bottom: 30px;
                right: 30px;
                padding: 15px 20px;
                border-radius: 8px;
                color: #fff;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                transform: translateY(100px);
                opacity: 0;
                transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
                z-index: 9999;
            }

            .custom-toast.show {
                transform: translateY(0);
                opacity: 1;
            }

            .toast-success {
                background-color: #10b981;
                border: 1px solid #059669;
            }

            .toast-error {
                background-color: #ef4444;
                border: 1px solid #b91c1c;
            }

            /* KANBAN KART DURUMLARI  */
            .card-status-active {
                background: #ffffff;
                border: 1px solid var(--border-color);
                cursor: grab;
            }

            .card-status-pending {
                background: #ffffff;
                border: 1px solid var(--border-color);
                cursor: default;
                opacity: 0.95;
            }

            .card-status-completed {
                background: #f0fdf4;
                border: 1px solid #86efac;
                cursor: default;
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                lucide.createIcons();
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                // Sürükle Bırak İşlemleri
                document.querySelectorAll('.kanban-cards-container').forEach(container => {
                    new Sortable(container, {
                        group: 'kanban_board',
                        animation: 180,
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        filter: '.filtered',
                        onMove: function(evt) {
                            const isPending = evt.dragged.querySelector('span') && evt.dragged
                                .querySelector('span').innerText.includes('ONAYDA');
                            return !isPending;
                        },
                        onEnd: function(evt) {
                            const cardEl = evt.item;
                            if (evt.from === evt.to && evt.oldIndex === evt.newIndex) return;

                            cardEl.style.opacity = '0.5';
                            fetch(`{{ url('/tasks') }}/${cardEl.dataset.taskId}/stage`, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    current_stage_id: evt.to.dataset.stageId
                                })
                            }).then(async r => {
                                const resData = await r.json();
                                cardEl.style.opacity = '1';
                                if (!r.ok) throw new Error(resData.message);

                                const oldCounter = evt.from.closest('.kanban-column')
                                    .querySelector('.card-count');
                                const newCounter = evt.to.closest('.kanban-column')
                                    .querySelector('.card-count');
                                if (oldCounter) oldCounter.textContent = evt.from
                                    .children.length;
                                if (newCounter) newCounter.textContent = evt.to.children
                                    .length;

                                showKanbanToast(resData.message, 'success');
                            }).catch(err => {
                                cardEl.style.opacity = '1';
                                if (evt.oldIndex !== undefined) {
                                    if (evt.oldIndex === evt.from.children.length) evt.from
                                        .appendChild(cardEl);
                                    else evt.from.insertBefore(cardEl, evt.from.children[evt
                                        .oldIndex]);
                                }
                                showKanbanToast(err.message || 'Taşıma başarısız', 'error');
                            });
                        }
                    });
                });

                function showKanbanToast(msg, type) {
                    const toast = document.getElementById('kanbanToast');
                    toast.className = `custom-toast toast-${type}`;
                    const icon = type === 'success' ? 'check-circle' : 'alert-triangle';
                    toast.innerHTML = `<i data-lucide="${icon}" style="width:18px;"></i> <span>${msg}</span>`;
                    lucide.createIcons();
                    toast.classList.add('show');
                    setTimeout(() => toast.classList.remove('show'), 3000);
                }
            });

            // Modal Yönetimi
            window.openClosureModal = function(taskId, requiresDocument) {
                const modal = document.getElementById('taskClosureModal');
                document.getElementById('closureForm').action = `{{ url('/tasks') }}/${taskId}/request-closure`;
                document.getElementById('closureDocumentInput').required = requiresDocument;
                document.getElementById('docRequiredStar').style.display = requiresDocument ? 'inline' : 'none';
                modal.style.display = 'flex';
            };

            window.closeClosureModal = function() {
                document.getElementById('closureForm').reset();
                document.getElementById('taskClosureModal').style.display = 'none';
            };

            window.addEventListener('click', e => {
                if (e.target === document.getElementById('taskClosureModal')) closeClosureModal();
            });
        </script>
    @endif

    {{-- SADECE AJANDA (CALENDAR) İÇİN GEREKLİ JS VE CSS --}}
    @if ($currentView === 'calendar')
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/tr.global.min.js"></script>
        <style>
            .fc-event {
                border: none !important;
                border-radius: 6px !important;
                padding: 3px 5px !important;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                transition: transform 0.2s;
                cursor: pointer;
            }

            .fc-event:hover {
                transform: scale(1.02);
                opacity: 0.9;
            }

            .fc-toolbar-title {
                color: var(--primary-color) !important;
                font-weight: 700 !important;
            }

            .fc-button-primary {
                background-color: var(--primary-color) !important;
                border-color: var(--primary-color) !important;
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                lucide.createIcons();

                var calendarEl = document.getElementById('fullCalendarBoard');
                var eventsData = @json($calendarEvents ?? []);

                var calendar = new FullCalendar.Calendar(calendarEl, {
                    locale: 'tr',
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                    },
                    events: eventsData,
                    eventContent: function(arg) {
                        let titleEl = document.createElement('div');
                        titleEl.innerHTML = `<strong>${arg.event.title}</strong>`;
                        return {
                            domNodes: [titleEl]
                        };
                    }
                });

                calendar.render();
            });
        </script>
    @endif
@endpush
