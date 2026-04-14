@extends('layouts.app')

@section('content')
    <div class="explorer-breadcrumb flex-between mb-20"
        style="background: var(--surface-color); padding: 20px; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">

        <div class="breadcrumb-path"
            style="display: flex; align-items: center; gap: 8px; font-size: 1.1rem; flex-wrap: wrap;">
            <a href="{{ route('folders.index') }}" class="crumb-link"
                style="display: flex; align-items: center; gap: 6px; color: var(--text-muted); text-decoration: none;">
                <i data-lucide="home" style="width: 18px;"></i> {{ __('Ana Dizin') }}
            </a>
            @foreach ($breadcrumbs as $crumb)
                <i data-lucide="chevron-right" style="width: 16px; color: #cbd5e1;"></i>
                @if ($loop->last)
                    <span class="crumb-current"
                        style="font-weight: 600; color: var(--primary-color);">{{ $crumb->name }}</span>
                @else
                    <a href="{{ route('folders.show', $crumb->id) }}" class="crumb-link"
                        style="color: var(--text-muted); text-decoration: none;">{{ $crumb->name }}</a>
                @endif
            @endforeach
        </div>

        <div class="explorer-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
            @if (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->id() === 1)
                <a href="{{ route('folders.edit', $folder->id) }}" class="btn btn-outline-secondary"
                    style="display: flex; align-items: center; gap: 8px; padding: 10px 15px; background: #fff;">
                    <i data-lucide="settings" style="width: 18px; color: var(--text-muted);"></i> {{ __('Düzenle') }}
                </a>
            @endif

            @can('delete', $folder)
                <form action="{{ route('folders.destroy', $folder->id) }}" method="POST" class="inline-form"
                    onsubmit="return confirm('{{ __('Bu klasörü sistemden kaldırmak (Soft Delete) istediğinize emin misiniz?') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"
                        style="display: flex; align-items: center; gap: 8px; padding: 10px 15px; background: #fff; transition: all 0.2s;">
                        <i data-lucide="trash-2" style="width: 18px;"></i> {{ __('Klasörü Sil') }}
                    </button>
                </form>
            @endcan

            <button class="btn btn-outline-primary" onclick="document.getElementById('newFolderModal').style.display='flex'"
                style="display: flex; align-items: center; gap: 8px; padding: 10px 15px;">
                <i data-lucide="folder-plus" style="width: 18px;"></i> {{ __('Yeni Klasör') }}
            </button>

            <a href="{{ route('documents.create', ['folder_id' => $folder->id]) }}" class="btn btn-primary"
                style="display: flex; align-items: center; gap: 8px; padding: 10px 15px;">
                <i data-lucide="file-up" style="width: 18px;"></i> {{ __('Belge Yükle') }}
            </a>
        </div>
    </div>

    @if ($folder->department_id)
        <div class="alert alert-info flex"
            style="align-items: center; gap: 10px; background: #f0fdfa; border: 1px solid #ccfbf1; color: #0f766e; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i data-lucide="shield-check" style="width: 20px;"></i>
            <span><strong>{{ __('İzole Klasör:') }}</strong> {{ __('Bu klasör ve içindekiler sadece') }}
                <strong>{{ $folder->department->name }}</strong> {{ __('departmanına aittir.') }}</span>
        </div>
    @endif

    <div class="file-explorer-container mt-20 mb-30">
        @if ($subfolders->count() > 0)
            <div
                style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #e2e8f0;">
                <i data-lucide="folders" style="color: var(--text-muted); width: 20px;"></i>
                <h3 class="explorer-section-title" style="margin: 0; font-size: 1.1rem; color: var(--secondary-color);">
                    {{ __('Alt Klasörler') }}</h3>
            </div>

            <div class="explorer-grid"
                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; margin-bottom: 30px;">
                @foreach ($subfolders as $sub)
                    <a href="{{ route('folders.show', $sub->id) }}" class="explorer-item folder-item"
                        style="background: var(--surface-color); border: 1px solid var(--border-color); border-radius: 10px; padding: 15px; display: flex; align-items: center; text-decoration: none; transition: all 0.2s ease;">
                        <div class="item-icon"
                            style="background: #f1f5f9; padding: 12px; border-radius: 8px; color: var(--text-muted); margin-right: 15px;">
                            <i data-lucide="folder"
                                style="width: 24px; height: 24px; fill: currentColor; opacity: 0.2;"></i>
                        </div>
                        <div class="item-details" style="flex: 1;">
                            <div class="item-name" style="font-weight: 600; color: var(--text-color); margin-bottom: 4px;">
                                {{ $sub->name }}</div>
                            <div class="item-meta" style="font-size: 0.8rem; color: var(--text-muted);">
                                {{ $sub->children->count() }} {{ __('Alt Klasör') }}</div>
                        </div>
                        <i data-lucide="chevron-right" style="color: #cbd5e1; width: 18px;"></i>
                    </a>
                @endforeach
            </div>
        @endif

        <div
            style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #e2e8f0;">
            <i data-lucide="files" style="color: var(--text-muted); width: 20px;"></i>
            <h3 class="explorer-section-title" style="margin: 0; font-size: 1.1rem; color: var(--secondary-color);">
                {{ __('Belgeler') }}</h3>
        </div>

        @if ($documents->count() > 0)
            <div class="explorer-grid"
                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 15px;">
                @foreach ($documents as $doc)
                    <a href="{{ route('documents.show', $doc->id) }}" class="explorer-item document-item"
                        style="background: var(--surface-color); border: 1px solid var(--border-color); border-radius: 10px; padding: 15px; display: flex; align-items: center; text-decoration: none; position: relative; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <div class="item-icon"
                            style="background: #eef2ff; padding: 12px; border-radius: 8px; color: var(--accent-color); margin-right: 15px;">
                            @if ($doc->category === 'Sözleşme')
                                <i data-lucide="scale" style="width: 24px; height: 24px;"></i>
                            @elseif($doc->category === 'İpotek/Rehin')
                                <i data-lucide="landmark" style="width: 24px; height: 24px;"></i>
                            @else
                                <i data-lucide="file-text" style="width: 24px; height: 24px;"></i>
                            @endif
                        </div>
                        <div class="item-details" style="flex: 1;">
                            <div class="item-name"
                                style="font-weight: 600; color: var(--text-color); margin-bottom: 4px; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $doc->title }}</div>
                            <div class="item-meta"
                                style="font-size: 0.8rem; color: var(--text-muted); display: flex; flex-direction: column; gap: 2px;">
                                <span><strong
                                        style="color: var(--secondary-color);">{{ $doc->document_number }}</strong></span>
                                <span><i data-lucide="user" style="width: 10px; display: inline-block;"></i>
                                    {{ $doc->currentVersion?->createdBy?->name ?? __('Bilinmiyor') }}</span>
                            </div>
                        </div>

                        @if ($doc->created_at->diffInDays(now()) < 2)
                            <div class="item-badge"
                                style="position: absolute; top: -8px; right: -8px; background: var(--danger-color); color: #fff; font-size: 0.65rem; font-weight: bold; padding: 3px 8px; border-radius: 12px; border: 2px solid #fff;">
                                {{ __('YENİ') }}</div>
                        @endif
                    </a>
                @endforeach
            </div>
        @else
            <div class="empty-state text-center"
                style="background: var(--surface-color); border: 1px dashed var(--border-color); border-radius: 12px; padding: 50px 20px;">
                <div style="display: flex; justify-content: center; margin-bottom: 15px;">
                    <i data-lucide="file-question"
                        style="width: 48px; height: 48px; color: var(--text-muted); opacity: 0.4;"></i>
                </div>
                <p class="text-muted" style="margin: 0; font-size: 1.05rem;">
                    {{ __('Bu klasörde henüz belge bulunmuyor.') }}</p>
            </div>
        @endif
    </div>

    @if (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->user()->can_manage_acl)
        <div class="card glass-card mt-30 mb-30" style="border-top: 4px solid var(--danger-color);">
            <div class="card-header"
                style="background: #f8fafc; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="shield" style="color: var(--danger-color);"></i>
                {{ __('Klasör Özel Yetki Matrisi (ACL)') }}
            </div>
            <div class="card-body" style="padding: 25px;">
                <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 20px;">
                    {{ __('Bu klasör için departman veya rol kısıtlamalarını ezerek belirli kullanıcılara özel erişim izni tanımlayabilirsiniz.') }}
                </p>

                <form action="{{ route('folders.permissions.store', $folder->id) }}" method="POST"
                    style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 25px; background: var(--bg-color); padding: 20px; border-radius: 8px; border: 1px dashed #cbd5e1;">
                    @csrf
                    <div style="flex: 2; min-width: 250px;">
                        <label
                            style="font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; display: block;">{{ __('Kullanıcı Seçin') }}
                            <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-control" required style="padding: 10px;">
                            <option value="">{{ __('-- Personel Seçiniz --') }}</option>
                            @foreach (\App\Models\User::where('is_active', true)->get() as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <label
                            style="font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; display: block;">{{ __('Yetki Seviyesi') }}
                            <span class="text-danger">*</span></label>
                        <select name="access_level" class="form-control" required style="padding: 10px;">
                            <option value="read">{{ __('Sadece Okuma (Read)') }}</option>
                            <option value="upload">{{ __('Belge Yükleme (Upload)') }}</option>
                            <option value="manage">{{ __('Tam Yönetim (Manage)') }}</option>
                        </select>
                    </div>
                    <div style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary"
                            style="height: 44px; padding: 0 25px; display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="plus" style="width: 16px;"></i> {{ __('Yetki Ekle') }}
                        </button>
                    </div>
                </form>

                <div style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
                    <table class="table" style="margin: 0;">
                        <thead
                            style="background: #f8fafc; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase;">
                            <tr>
                                <th style="padding: 15px;">{{ __('Kullanıcı Bilgisi') }}</th>
                                <th style="padding: 15px;">{{ __('Yetki Seviyesi') }}</th>
                                <th class="text-right" style="padding: 15px;">{{ __('İşlem') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($folder->specificUsers as $specUser)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 15px; font-weight: 500; color: var(--text-color);">
                                        {{ $specUser->name }}</td>
                                    <td style="padding: 15px;">
                                        @if ($specUser->pivot->access_level === 'manage')
                                            <span class="badge badge-danger">{{ __('TAM YÖNETİM') }}</span>
                                        @elseif($specUser->pivot->access_level === 'upload')
                                            <span class="badge badge-warning">{{ __('YÜKLEME') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('SADECE OKUMA') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right" style="padding: 15px;">
                                        <form
                                            action="{{ route('folders.permissions.destroy', [$folder->id, $specUser->id]) }}"
                                            method="POST"
                                            onsubmit="return confirm('{{ __('Bu kullanıcının yetkisini kaldırmak istediğinize emin misiniz?') }}');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                style="padding: 6px 12px; display: inline-flex; align-items: center; gap: 4px;">
                                                <i data-lucide="trash-2" style="width:14px;"></i> {{ __('Kaldır') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted" style="padding: 30px;">
                                        {{ __('Bu klasör için tanımlanmış özel bir istisna yetkisi bulunmuyor.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div id="newFolderModal" class="modal-overlay"
        style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(4px);">
        <div class="modal-content"
            style="background: #fff; width: 100%; max-width: 480px; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden;">
            <div class="modal-header"
                style="padding: 20px 25px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <h2
                    style="margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 8px; color: var(--primary-color);">
                    <i data-lucide="corner-down-right" style="color: var(--accent-color);"></i>
                    {{ __('Alt Klasör Oluştur') }}
                </h2>
                <button type="button" class="close-modal"
                    onclick="document.getElementById('newFolderModal').style.display='none'"
                    style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
            </div>

            <div class="modal-body" style="padding: 25px;">
                <form action="{{ route('folders.store') }}" method="POST" class="modern-form">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $folder->id }}">

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label
                            style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: var(--text-muted);">{{ __('Hedef Dizin (Üst Klasör)') }}</label>
                        <div
                            style="display: flex; align-items: center; gap: 10px; background: #f1f5f9; padding: 12px 15px; border-radius: 6px; border: 1px solid #e2e8f0; color: var(--text-color); font-weight: 500; user-select: none;">
                            <i data-lucide="folder-open" style="width: 18px; color: var(--accent-color);"></i>
                            {{ $folder->name }}
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 25px;">
                        <label
                            style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 8px; color: var(--secondary-color);">{{ __('Yeni Alt Klasör Adı') }}
                            <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required autofocus
                            placeholder="{{ __('Örn: 2026 Bütçe Raporları') }}"
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 1rem;">
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label
                            style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 8px; color: var(--secondary-color);">{{ __('Klasör Öneki (Opsiyonel)') }}</label>
                        <input type="text" name="prefix" class="form-control"
                            placeholder="{{ __('Örn: IK veya TL') }}"
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                        <small
                            class="text-muted">{{ __('Ana klasöre IK, alt klasöre TL yazarsanız belge IK-TL-001 olur.') }}</small>
                    </div>

                    @if ($folder->department_id)
                        <div class="alert alert-warning"
                            style="padding: 15px; background: #f0fdfa; border: 1px solid #ccfbf1; border-radius: 6px; font-size: 0.85rem; display: flex; gap: 10px; margin-bottom: 20px;">
                            <i data-lucide="shield" style="color: #0d9488; width: 24px; flex-shrink: 0;"></i>
                            <span style="color: #0f766e;">
                                <strong>{{ __('Güvenlik Mirası:') }}</strong>
                                {{ __('Bu alt klasör, üst klasörün yetki kurallarını miras alacak ve sadece') }}
                                <strong>{{ $folder->department->name }}</strong>
                                {{ __('departmanı tarafından erişilebilir olacaktır.') }}
                            </span>
                        </div>
                    @endif

                    <div class="form-actions"
                        style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 15px; border-top: 1px solid var(--border-color);">
                        <button type="button" class="btn btn-outline-secondary"
                            onclick="document.getElementById('newFolderModal').style.display='none'"
                            style="padding: 10px 20px;">{{ __('İptal') }}</button>
                        <button type="submit" class="btn btn-primary"
                            style="padding: 10px 25px; display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="folder-plus" style="width: 16px;"></i> {{ __('Alt Klasörü Oluştur') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();

            const subModal = document.getElementById('newFolderModal');
            window.addEventListener('click', function(e) {
                if (e.target === subModal) subModal.style.display = 'none';
            });
        });
    </script>
@endpush
