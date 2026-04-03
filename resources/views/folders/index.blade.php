@extends('layouts.app')

@section('content')
    <div class="explorer-breadcrumb flex-between mb-20"
        style="background: var(--surface-color); padding: 20px; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
        <div class="breadcrumb-path" style="display: flex; align-items: center; gap: 10px;">
            <i data-lucide="hard-drive" style="color: var(--primary-color); width: 24px; height: 24px;"></i>
            <span class="crumb-current" style="font-size: 1.25rem; font-weight: 700; color: var(--primary-color);">Ana Dizin
                (Kök Klasörler)</span>
        </div>

        <div class="explorer-actions">
            @if (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || auth()->id() === 1)
                <button class="btn btn-primary" onclick="document.getElementById('newRootFolderModal').style.display='flex'"
                    style="display: flex; align-items: center; gap: 8px; padding: 10px 20px;">
                    <i data-lucide="folder-plus" style="width: 18px;"></i> Yeni Ana Klasör Oluştur
                </button>
            @endif
        </div>
    </div>

    <div class="alert alert-info mb-20 flex"
        style="align-items: flex-start; gap: 12px; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 16px; border-radius: 8px;">
        <i data-lucide="info" style="width: 20px; flex-shrink: 0; margin-top: 2px;"></i>
        <div>
            <strong>Dosya Yöneticisi:</strong> Aşağıda erişim yetkiniz olan ana dizinleri görmektesiniz. Alt klasörleri ve
            belgeleri görmek için bir klasörün içine tıklayın.
        </div>
    </div>

    <div class="file-explorer-container">
        @if (isset($folders) && $folders->count() > 0)
            <div class="explorer-grid"
                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                @foreach ($folders as $folder)
                    <div class="explorer-item folder-item"
                        style="background: var(--surface-color); border: 1px solid var(--border-color); border-radius: 12px; display: flex; flex-direction: column; overflow: hidden; box-shadow: var(--card-shadow); transition: all 0.2s ease;">

                        <a href="{{ route('folders.show', $folder->id) }}"
                            style="text-decoration: none; color: inherit; display: flex; flex-direction: column; flex: 1; padding: 25px 20px; position: relative;">

                            @if ($folder->departments->count() > 0)
                                <div class="item-badge"
                                    style="position: absolute; top: 15px; right: 15px; background: #fef3c7; color: #b45309; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; border: 1px solid #fde68a; max-width: 60%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                    title="{{ $folder->departments->pluck('name')->join(', ') }}">
                                    {{ $folder->departments->count() }} DEPARTMAN
                                </div>
                            @else
                                <div class="item-badge"
                                    style="position: absolute; top: 15px; right: 15px; background: #dcfce7; color: #166534; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; border: 1px solid #bbf7d0;">
                                    GENEL (AÇIK)
                                </div>
                            @endif

                            <div style="display: flex; align-items: flex-start; width: 100%; margin-top: 25px; gap: 15px;">
                                <div class="item-icon"
                                    style="background: #f1f5f9; padding: 15px; border-radius: 12px; color: var(--accent-color); flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                                    <i data-lucide="folder" style="width: 32px; height: 32px;"></i>
                                </div>

                                <div class="item-details" style="flex: 1; min-width: 0;">
                                    <div class="item-name"
                                        style="font-size: 1.15rem; font-weight: 600; color: var(--text-color); margin-bottom: 8px; white-space: normal; word-break: break-word; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4;"
                                        title="{{ $folder->name }}">
                                        {{ $folder->name }}
                                    </div>
                                    <div class="item-meta"
                                        style="font-size: 0.85rem; color: var(--text-muted); display: flex; flex-direction: column; gap: 4px;">
                                        <span style="display: flex; align-items: center; gap: 6px;"><i
                                                data-lucide="folder-open" style="width: 14px;"></i>
                                            {{ $folder->children()->count() }} Alt Klasör</span>
                                        <span style="display: flex; align-items: center; gap: 6px;"><i
                                                data-lucide="file-text" style="width: 14px;"></i>
                                            {{ $folder->documents()->count() }} Belge</span>
                                    </div>
                                </div>
                            </div>
                        </a>

                        @can('delete', $folder)
                            <div
                                style="background: #f8fafc; border-top: 1px solid var(--border-color); padding: 12px 20px; display: flex; justify-content: flex-end;">
                                <form action="{{ route('folders.destroy', $folder->id) }}" method="POST" style="margin: 0;"
                                    onsubmit="return confirm('Bu klasörü sistemden kaldırmak (Soft Delete) istediğinize emin misiniz?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Klasörü Sil"
                                        style="padding: 6px 12px; border-radius: 6px; display: flex; align-items: center; gap: 6px; background: #fff;">
                                        <i data-lucide="trash-2" style="width: 16px;"></i> Sil
                                    </button>
                                </form>
                            </div>
                        @endcan

                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state text-center"
                style="background: var(--surface-color); border: 1px dashed var(--border-color); border-radius: 12px; padding: 60px 20px;">
                <div style="display: flex; justify-content: center; margin-bottom: 20px;">
                    <i data-lucide="folder-search"
                        style="width: 64px; height: 64px; color: var(--text-muted); opacity: 0.5;"></i>
                </div>
                <h3 style="color: var(--primary-color); margin-bottom: 10px;">Ana Dizin Bomboş</h3>
                <p class="text-muted">Sistemde erişim yetkiniz olan hiçbir kök klasör bulunmuyor.</p>
            </div>
        @endif
    </div>

    <div id="newRootFolderModal" class="modal-overlay"
        style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(4px);">
        <div class="modal-content"
            style="background: #fff; width: 100%; max-width: 600px; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden;">

            <div class="modal-header"
                style="padding: 20px 25px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <h2 style="margin: 0; font-size: 1.25rem; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="folder-plus" style="color: var(--accent-color);"></i> Ana Dizin (Kök Klasör) Oluştur
                </h2>
                <button type="button" class="close-modal"
                    onclick="document.getElementById('newRootFolderModal').style.display='none'"
                    style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
            </div>

            <div class="modal-body" style="padding: 25px;">
                <form action="{{ route('folders.store') }}" method="POST" class="modern-form">
                    @csrf

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label
                            style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 8px; color: var(--secondary-color);">Klasör
                            Adı <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required autofocus
                            placeholder="Örn: İnsan Kaynakları"
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 1rem;">
                    </div>
                    <div class="form-group" style="margin-top: 15px;">
                        <label
                            style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 8px; color: var(--secondary-color);">
                            Klasör Öneki (Opsiyonel)
                        </label>
                        <input type="text" name="prefix" class="form-control" placeholder="Örn: TL-IG"
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 1rem;">
                        <small class="text-muted" style="display: block; margin-top: 5px; font-size: 0.8rem;">
                            Boş bırakılırsa, sistem otomatik olarak bir üst klasörün önekini kullanır (Miras Alma).
                        </small>
                    </div>

                    <div class="form-group" style="margin-bottom: 25px;">
                        <label
                            style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 8px; color: var(--secondary-color);">Erişim
                            Kısıtlaması (Departman Seçimi)</label>

                        <div class="department-checkbox-list"
                            style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; max-height: 250px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 8px; padding: 15px; background: #f8fafc;">
                            @foreach ($departments as $dept)
                                <label class="dept-label"
                                    style="display: flex; align-items: center; cursor: pointer; padding: 10px 12px; background: #fff; border: 2px solid #e2e8f0; border-radius: 8px; transition: all 0.2s ease;">
                                    <input type="checkbox" name="department_ids[]" value="{{ $dept->id }}"
                                        class="dept-checkbox"
                                        style="margin-right: 10px; width: 18px; height: 18px; accent-color: var(--primary-color); cursor: pointer;">
                                    <span class="dept-text"
                                        style="font-size: 0.95rem; color: var(--text-color); font-weight: 500;">{{ $dept->name }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="alert alert-warning"
                            style="margin-top: 15px; padding: 12px; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 6px; font-size: 0.85rem; display: flex; gap: 10px; align-items: flex-start;">
                            <i data-lucide="info" style="color: #d97706; width: 18px; flex-shrink: 0;"></i>
                            <span style="color: #92400e;"><strong>İpucu:</strong> Hiçbirini seçmezseniz klasör "Herkese
                                Açık" olur. Birden fazla seçerseniz departmanlara ortak paylaştırılır.</span>
                        </div>
                    </div>

                    <div class="form-actions"
                        style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 10px; border-top: 1px solid var(--border-color);">
                        <button type="button" class="btn btn-outline-secondary"
                            onclick="document.getElementById('newRootFolderModal').style.display='none'"
                            style="padding: 10px 20px;">İptal</button>
                        <button type="submit" class="btn btn-primary"
                            style="padding: 10px 25px; display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="save" style="width: 16px;"></i> Klasörü Oluştur
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

            // Dışarıya tıklayınca modalı kapatma işlemi
            const rootModal = document.getElementById('newRootFolderModal');
            window.addEventListener('click', function(e) {
                if (e.target === rootModal) {
                    rootModal.style.display = 'none';
                }
            });
        });
        // --- DEPARTMAN SEÇİMİ UI EFEKTİ ---
        const deptCheckboxes = document.querySelectorAll('.dept-checkbox');

        deptCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const label = this.closest('.dept-label');
                const text = label.querySelector('.dept-text');

                if (this.checked) {
                    // Seçildiğinde: Çerçeve mavi, arkaplan uçuk mavi, yazı kalın!
                    label.style.borderColor = 'var(--primary-color)';
                    label.style.backgroundColor = '#f0f9ff';
                    text.style.color = 'var(--primary-color)';
                    text.style.fontWeight = '700';
                } else {
                    // Seçim kaldırıldığında: Orijinal mat haline dön
                    label.style.borderColor = '#e2e8f0';
                    label.style.backgroundColor = '#fff';
                    text.style.color = 'var(--text-color)';
                    text.style.fontWeight = '500';
                }
            });
        });
    </script>
@endpush
