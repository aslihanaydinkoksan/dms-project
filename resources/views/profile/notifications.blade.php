@extends('layouts.app')

@section('content')
    <div class="page-header mb-20 flex-between">
        <div>
            <h1 class="page-title">⚙️ Bildirim Tercihlerim</h1>
            <p class="text-muted">Hangi durumlarda e-posta veya sistem içi bildirim (Zil 🔔) almak istediğinizi
                kişiselleştirin.</p>
        </div>
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">Geri Dön</a>
        </div>
    </div>

    <div class="card glass-card p-30" style="max-width: 900px; margin: 0 auto;">

        @if (session('success'))
            <div class="alert alert-success mb-20">✅ {{ session('success') }}</div>
        @endif

        <form action="{{ route('profile.notifications.update') }}" method="POST" class="modern-form">
            @csrf

            <div class="table-responsive">
                <table class="table modern-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 15px; text-align: left;">Bildirim Türü / Olay</th>
                            <th style="padding: 15px; text-align: center; width: 150px;">Sistem İçi (Zil) 🔔</th>
                            <th style="padding: 15px; text-align: center; width: 150px;">E-Posta ✉️</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 15px;">
                                <strong style="font-size: 1.05rem; color: var(--text-color);">İş Akışı ve Onay
                                    Süreçleri</strong><br>
                                <span class="text-muted" style="font-size: 0.85rem;">Belge onayınıza sunulduğunda,
                                    onaylandığında veya reddedildiğinde tetiklenir.</span>
                            </td>
                            <td style="padding: 15px; text-align: center;">
                                <label class="custom-checkbox d-inline-block">
                                    <input type="checkbox" name="workflow_action_db"
                                        {{ $prefs['workflow_action']['database'] ?? true ? 'checked' : '' }}>
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                            <td style="padding: 15px; text-align: center;">
                                <label class="custom-checkbox d-inline-block">
                                    <input type="checkbox" name="workflow_action_mail"
                                        {{ $prefs['workflow_action']['mail'] ?? true ? 'checked' : '' }}>
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                        </tr>

                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 15px;">
                                <strong style="font-size: 1.05rem; color: var(--text-color);">Fiziksel Evrak
                                    Zimmeti</strong><br>
                                <span class="text-muted" style="font-size: 0.85rem;">Islak imzalı bir evrak size fiziksel
                                    olarak atandığında ve teslim almanız gerektiğinde tetiklenir.</span>
                            </td>
                            <td style="padding: 15px; text-align: center;">
                                <label class="custom-checkbox d-inline-block">
                                    <input type="checkbox" name="physical_assigned_db"
                                        {{ $prefs['physical_assigned']['database'] ?? true ? 'checked' : '' }}>
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                            <td style="padding: 15px; text-align: center;">
                                <label class="custom-checkbox d-inline-block">
                                    <input type="checkbox" name="physical_assigned_mail"
                                        {{ $prefs['physical_assigned']['mail'] ?? true ? 'checked' : '' }}>
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                        </tr>

                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 15px;">
                                <strong style="font-size: 1.05rem; color: var(--text-color);">Belge Revizyonu ve
                                    Kilitler</strong><br>
                                <span class="text-muted" style="font-size: 0.85rem;">Sahibi olduğunuz bir belge başkası
                                    tarafından kilitlendiğinde veya yeni versiyon yüklendiğinde tetiklenir.</span>
                            </td>
                            <td style="padding: 15px; text-align: center;">
                                <label class="custom-checkbox d-inline-block">
                                    <input type="checkbox" name="document_revision_db"
                                        {{ $prefs['document_revision']['database'] ?? true ? 'checked' : '' }}>
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                            <td style="padding: 15px; text-align: center;">
                                <label class="custom-checkbox d-inline-block">
                                    <input type="checkbox" name="document_revision_mail"
                                        {{ $prefs['document_revision']['mail'] ?? true ? 'checked' : '' }}>
                                    <span class="checkmark"></span>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="alert alert-info mt-20" style="font-size: 0.85rem; border-left: 4px solid var(--primary-color);">
                ℹ️ <strong>Önemli Bilgi:</strong> Sistem yöneticisi tarafından kurum geneli e-posta gönderimi kapatılmışsa,
                buradaki e-posta tercihleriniz açık olsa dahi e-posta almazsınız. Ancak sistem içi (Zil) bildirimleri her
                zaman çalışmaya devam eder.
            </div>

            <div class="form-actions mt-30 text-right">
                <button type="submit" class="btn btn-primary" style="padding: 12px 30px; font-size: 1.05rem;">
                    💾 Tercihlerimi Kaydet
                </button>
            </div>
        </form>
    </div>
@endsection
