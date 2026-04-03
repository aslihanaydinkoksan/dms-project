@extends('layouts.app')

@section('content')
    <div class="form-container" style="max-width: 700px; margin: 5vh auto; width: 100%;">

        <div class="page-header flex-between" style="margin-bottom: 25px;">
            <div>
                <h1 class="page-title" style="margin-bottom: 5px;">✏️ Klasör Düzenle</h1>
                <p class="text-muted"><strong style="color: var(--primary-color);">{{ $folder->name }}</strong> adlı klasörün
                    özelliklerini ve konumunu değiştirin.</p>
            </div>
            <a href="{{ route('folders.show', $folder->id) }}" class="btn btn-outline-secondary"
                style="border-radius: 20px; padding: 8px 20px;">
                ← İptal ve Geri Dön
            </a>
        </div>

        <div class="card glass-card"
            style="box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid rgba(255,255,255,0.8);">
            <form action="{{ route('folders.update', $folder->id) }}" method="POST" class="modern-form">
                @csrf
                @method('PUT')

                <div class="card-body" style="padding: 30px;">

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label"
                            style="font-weight: 600; color: var(--secondary-color); margin-bottom: 8px; display: block;">
                            Klasör Adı <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $folder->name) }}" required
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 1.05rem;">
                        @error('name')
                            <div class="text-danger mt-1" style="font-size: 0.85rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label"
                            style="font-weight: 600; color: var(--secondary-color); margin-bottom: 8px; display: block;">
                            Klasör Öneki (Opsiyonel)
                        </label>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="text" name="prefix" class="form-control @error('prefix') is-invalid @enderror"
                                value="{{ old('prefix', $folder->prefix) }}" placeholder="Örn: IK, TL, FR"
                                style="flex: 1; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-family: monospace; font-size: 1.1rem; color: var(--primary-color);">
                        </div>
                        <small class="text-muted" style="display: block; margin-top: 8px; font-size: 0.85rem;">
                            <i data-lucide="info" style="width: 14px; vertical-align: middle;"></i>
                            Boş bırakırsanız üst klasörün önekini kullanır (Miras alır). Kendi önekini girerseniz birleşerek
                            devam eder (Örn: IK-TL).
                        </small>
                        @error('prefix')
                            <div class="text-danger mt-1" style="font-size: 0.85rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group" style="margin-bottom: 30px;">
                        <label class="form-label"
                            style="font-weight: 600; color: var(--secondary-color); margin-bottom: 8px; display: block;">
                            Bağlı Olduğu Klasör (Konum)
                        </label>
                        <select name="parent_id" class="form-control"
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                            <option value="">-- Ana Dizin (En Üst Seviye) --</option>
                            @foreach ($allFolders as $parentFolder)
                                <option value="{{ $parentFolder->id }}"
                                    {{ old('parent_id', $folder->parent_id) == $parentFolder->id ? 'selected' : '' }}>
                                    📁 {{ $parentFolder->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted" style="display: block; margin-top: 8px; font-size: 0.85rem;">
                            Klasörü başka bir dizine taşımak isterseniz buradan seçebilirsiniz.
                        </small>
                        @error('parent_id')
                            <div class="text-danger mt-1" style="font-size: 0.85rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div style="height: 1px; background: var(--border-color); margin: 30px 0;"></div>

                    <div class="form-actions text-right">
                        <button type="submit" class="btn btn-primary"
                            style="padding: 12px 30px; font-size: 1.05rem; font-weight: 600; border-radius: 30px; box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);">
                            💾 Değişiklikleri Kaydet
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
@endpush
