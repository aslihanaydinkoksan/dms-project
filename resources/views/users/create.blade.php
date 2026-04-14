@extends('layouts.app')

@section('content')
    <div class="form-container" style="max-width: 800px; margin: 5vh auto; width: 100%;">

        <div class="page-header flex-between" style="margin-bottom: 25px;">
            <div>
                <h1 class="page-title" style="margin-bottom: 5px;">👤 {{ __('Yeni Personel Ekle') }}</h1>
                <p class="text-muted">{{ __('Sisteme yeni bir kullanıcı tanımlayın ve erişim yetkilerini belirleyin.') }}</p>
            </div>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary"
                style="border-radius: 20px; padding: 8px 20px;">
                ← {{ __('Listeye Dön') }}
            </a>
        </div>

        <div class="card glass-card"
            style="box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid rgba(255,255,255,0.8);">
            <form action="{{ route('users.store') }}" method="POST" class="modern-form">
                @csrf

                <div
                    style="background: #f8fafc; padding: 15px 20px; margin: -20px -20px 20px -20px; border-bottom: 1px solid #e2e8f0; border-radius: 8px 8px 0 0;">
                    <h3 class="section-title" style="margin: 0; font-size: 1.05rem;">{{ __('Genel Bilgiler') }}</h3>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">{{ __('Ad Soyad') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" placeholder="{{ __('Örn: Ahmet Yılmaz') }}" required>
                        @error('name')
                            <div class="form-error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ __('E-Posta Adresi') }} <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}" placeholder="ahmet.yilmaz@sirket.com" required>
                        @error('email')
                            <div class="form-error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ __('Geçici Şifre') }} <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                            placeholder="{{ __('Sisteme ilk giriş şifresi') }}" required>
                        @error('password')
                            <div class="form-error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ __('Departman') }}</label>
                        <select name="department_id" class="form-control @error('department_id') is-invalid @enderror">
                            <option value="">{{ __('-- Bağımsız (Departmansız) --') }}</option>

                            @foreach ($departments as $unit => $unitDepartments)
                                <optgroup label="🏢 {{ $unit }}">
                                    @foreach ($unitDepartments as $dept)
                                        <option value="{{ $dept->id }}"
                                            {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach

                        </select>
                        @error('department_id')
                            <div class="form-error-text">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-section-divider"></div>

                <div class="flex-between" style="margin-bottom: 20px;">
                    <h3 class="section-title" style="margin: 0;">{{ __('Sistem Rolleri') }}</h3>
                    <span class="badge badge-info"
                        style="background: #e1f5fe; color: #0288d1; border: 1px solid #b3e5fc;">{{ __('Birden fazla rol seçebilirsiniz') }}</span>
                </div>

                <div class="checkbox-grid">
                    @foreach ($roles as $role)
                        <label class="checkbox-card">
                            <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                                {{ in_array($role->name, old('roles', [])) ? 'checked' : '' }}>
                            <span class="checkbox-label">{{ $role->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('roles')
                    <div class="form-error-text mt-15">{{ $message }}</div>
                @enderror

                <div class="form-section-divider" style="margin-top: 30px;"></div>

                <div class="flex-between align-items-center mt-20"
                    style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div class="form-group" style="margin: 0;">
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_active" value="1"
                                {{ old() ? (old('is_active') ? 'checked' : '') : 'checked' }}>
                            <span class="toggle-slider"></span>
                            <span class="toggle-text"
                                style="font-weight: 600;">{{ __('Kullanıcı Hesabı Aktif Olsun') }}</span>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"
                            style="padding: 12px 30px; font-size: 1.05rem; font-weight: 600; border-radius: 30px; box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);">
                            💾 {{ __('Kullanıcıyı Kaydet') }}
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
@endsection
