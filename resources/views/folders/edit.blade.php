@extends('layouts.app')

@section('content')
    <div class="form-container" style="max-width: 700px; margin: 5vh auto; width: 100%;">

        <div class="page-header flex-between" style="margin-bottom: 25px;">
            <div>
                <h1 class="page-title" style="margin-bottom: 5px;">✏️ {{ __('Klasör Düzenle') }}</h1>
                <p class="text-muted"><strong style="color: var(--primary-color);">{{ $folder->name }}</strong>
                    {{ __('adlı klasörün özelliklerini ve konumunu değiştirin.') }}</p>
            </div>
            <a href="{{ route('folders.show', $folder->id) }}" class="btn btn-outline-secondary"
                style="border-radius: 20px; padding: 8px 20px;">
                ← {{ __('İptal ve Geri Dön') }}
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
                            {{ __('Klasör Adı') }} <span class="text-danger">*</span>
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
                            {{ __('Klasör Öneki (Opsiyonel)') }}
                        </label>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="text" name="prefix" class="form-control @error('prefix') is-invalid @enderror"
                                value="{{ old('prefix', $folder->prefix) }}" placeholder="{{ __('Örn: IK, TL, FR') }}"
                                style="flex: 1; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-family: monospace; font-size: 1.1rem; color: var(--primary-color);">
                        </div>
                        <small class="text-muted" style="display: block; margin-top: 8px; font-size: 0.85rem;">
                            <i data-lucide="info" style="width: 14px; vertical-align: middle;"></i>
                            {{ __('Boş bırakırsanız üst klasörün önekini kullanır (Miras alır). Kendi önekini girerseniz birleşerek devam eder (Örn: IK-TL).') }}
                        </small>
                        @error('prefix')
                            <div class="text-danger mt-1" style="font-size: 0.85rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- YENİ EKLENEN: DEPARTMAN SEÇİMİ --}}
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label"
                            style="font-weight: 600; color: var(--secondary-color); margin-bottom: 8px; display: block;">
                            <i data-lucide="building-2" style="width: 18px; vertical-align: text-bottom;"></i>
                            {{ __('Erişime Açık Departmanlar') }}
                        </label>
                        <div
                            style="background: #f8fafc; border: 1px solid var(--border-color); padding: 15px; border-radius: 8px; max-height: 200px; overflow-y: auto;">

                            @if (empty($folder->parent_id))
                                {{-- EĞER KÖK KLASÖR İSE DEĞİŞTİREBİLİR --}}
                                <div class="row">
                                    @foreach ($departments as $dept)
                                        <div class="col-md-6 mb-2">
                                            <label
                                                style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer; color: var(--text-color);">
                                                <input type="checkbox" name="department_ids[]" value="{{ $dept->id }}"
                                                    {{ in_array($dept->id, old('department_ids', $folderDepartmentIds)) ? 'checked' : '' }}
                                                    style="width: 16px; height: 16px; accent-color: var(--primary-color);">
                                                {{ $dept->name }}
                                                @if ($dept->unit)
                                                    <span class="text-muted"
                                                        style="font-size: 0.75rem;">({{ $dept->unit }})</span>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="text-muted mt-2 d-block">
                                    {{ __('Hiçbirini seçmezseniz klasör "Herkese Açık (Global)" olur.') }}
                                </small>
                            @else
                                {{-- EĞER ALT KLASÖR İSE ÜST KLASÖRDEN MİRAS ALIR, DEĞİŞTİREMEZ --}}
                                <div class="alert alert-warning mb-0" style="padding: 10px; font-size: 0.85rem;">
                                    <i data-lucide="lock" style="width: 16px;"></i>
                                    {{ __('Bu bir alt klasör olduğu için departman izolasyonu üst klasörden (Parent) miras alınır. Değiştirmek için üst klasörü düzenleyin.') }}
                                </div>
                            @endif

                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 30px;">
                        <label class="form-label"
                            style="font-weight: 600; color: var(--secondary-color); margin-bottom: 8px; display: block;">
                            {{ __('Bağlı Olduğu Klasör (Konum)') }}
                        </label>

                        <select name="parent_id" class="form-control"
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                            <option value="">{{ __('-- Ana Dizin (En Üst Seviye) --') }}</option>

                            @foreach ($flatFolders as $id => $path)
                                <option value="{{ $id }}"
                                    {{ old('parent_id', $folder->parent_id) == $id ? 'selected' : '' }}>
                                    📁 {{ $path }}
                                </option>
                            @endforeach

                        </select>

                        <small class="text-muted" style="display: block; margin-top: 8px; font-size: 0.85rem;">
                            {{ __('Klasörü sadece dosya yükleme (upload) veya yönetim yetkiniz olan dizinlere taşıyabilirsiniz.') }}
                        </small>
                        @error('parent_id')
                            <div class="text-danger mt-1" style="font-size: 0.85rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div style="height: 1px; background: var(--border-color); margin: 30px 0;"></div>

                    <div class="form-actions text-right">
                        <button type="submit" class="btn btn-primary"
                            style="padding: 12px 30px; font-size: 1.05rem; font-weight: 600; border-radius: 30px; box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);">
                            💾 {{ __('Değişiklikleri Kaydet') }}
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
