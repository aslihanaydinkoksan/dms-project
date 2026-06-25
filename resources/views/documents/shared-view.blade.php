<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KÖKSAN DMS - Güvenli Belge Görüntüleyici</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #ce1126;
            --primary-light: rgba(206, 17, 38, 0.08);
            --text-dark: #111827;
            --text-muted: #6b7280;
            --border: rgba(255, 255, 255, 0.35);
            --card-bg: rgba(255, 255, 255, 0.72);
            --shadow: 0 20px 50px rgba(15, 23, 42, 0.12);
        }

        html,
        body {
            height: 100%;
            font-family: Inter, "Segoe UI", sans-serif;
            overflow: hidden;
            color: var(--text-dark);
            background:
                radial-gradient(circle at top left,
                    rgba(206, 17, 38, 0.12),
                    transparent 30%),
                radial-gradient(circle at top right,
                    rgba(59, 130, 246, 0.08),
                    transparent 25%),
                linear-gradient(135deg,
                    #f8fafc,
                    #eef2f7);
        }

        body {
            padding: 16px;
        }

        .app-wrapper {
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .topbar {
            min-height: 88px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 20px 28px;

            background: var(--card-bg);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);

            border: 1px solid var(--border);
            border-radius: 24px;

            box-shadow: var(--shadow);
        }

        .document-section {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .document-icon {
            width: 56px;
            height: 56px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: linear-gradient(135deg,
                    var(--primary),
                    #e53950);

            color: white;
            border-radius: 18px;
            font-size: 24px;
            font-weight: 700;

            box-shadow: 0 10px 25px rgba(206, 17, 38, .25);
        }

        .document-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 6px;
        }

        .document-meta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 999px;

            background: var(--primary-light);
            color: var(--primary);

            font-size: 12px;
            font-weight: 600;
        }

        .license-box {
            text-align: right;
        }

        .license-title {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .license-email {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .expiry {
            margin-top: 6px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .viewer-wrapper {
            flex: 1;

            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);

            border-radius: 28px;
            overflow: hidden;

            border: 1px solid rgba(255, 255, 255, .4);

            box-shadow: var(--shadow);
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
            background: white;
        }

        .unsupported-view {
            height: 100%;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 40px;
        }

        .download-card {
            max-width: 520px;
            text-align: center;

            background: white;
            border-radius: 24px;

            padding: 40px;

            box-shadow: 0 20px 40px rgba(0, 0, 0, .08);
        }

        .download-icon {
            width: 72px;
            height: 72px;

            margin: 0 auto 20px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 20px;

            background: var(--primary-light);
            color: var(--primary);

            font-size: 30px;
            font-weight: bold;
        }

        .download-card h2 {
            margin-bottom: 12px;
            font-size: 22px;
        }

        .download-card p {
            color: var(--text-muted);
            line-height: 1.7;
            margin-bottom: 24px;
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            text-decoration: none;

            background: linear-gradient(135deg,
                    var(--primary),
                    #e53950);

            color: white;

            padding: 14px 28px;

            border-radius: 14px;

            font-weight: 600;

            transition: .25s ease;

            box-shadow: 0 10px 25px rgba(206, 17, 38, .25);
        }

        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(206, 17, 38, .35);
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 18px;
            }

            .license-box {
                text-align: left;
            }

            .document-meta {
                gap: 8px;
            }

            .document-title {
                font-size: 16px;
            }

            .viewer-wrapper {
                border-radius: 20px;
            }
        }
    </style>
</head>

<body>

    <div class="app-wrapper">

        <div class="topbar">

            <div class="document-section">

                <div class="document-icon">
                    📄
                </div>

                <div>

                    <div class="document-title">
                        {{ $document->title }}
                    </div>

                    <div class="document-meta">

                        <span class="badge">
                            Evrak No: {{ $document->document_number }}
                        </span>

                        <span class="badge">
                            Versiyon {{ $currentVersion->version_number }}
                        </span>

                    </div>

                </div>

            </div>

            <div class="license-box">

                <div class="license-title">
                    Bu belge aşağıdaki kullanıcı için lisanslanmıştır
                </div>

                <div class="license-email">
                    {{ $share->email }}
                </div>

                <div class="expiry">
                    Son Erişim:
                    {{ $share->expires_at ? $share->expires_at->format('d.m.Y H:i') : 'Süresiz' }}
                </div>

            </div>

        </div>

        <div class="viewer-wrapper">
            @php
                $mimeType = $currentVersion->mime_type;
                $isPdf = str_contains($mimeType, 'pdf');
                $isImage = str_starts_with($mimeType, 'image/');
            @endphp

            @if ($isPdf)
                <iframe src="{{ route('shared.document.download', ['token' => $share->token]) }}#toolbar=0"
                    type="application/pdf"></iframe>
            @elseif ($isImage)
                <div style="height: 100%; display: flex; align-items: center; justify-content: center; overflow: auto;">
                    <img src="{{ route('shared.document.download', ['token' => $share->token]) }}" alt="Önizleme"
                        style="max-width: 90%; max-height: 90%; object-fit: contain;">
                </div>
            @else
                {{-- Excel, Word veya diğerleri için indirme kartı --}}
                <div class="unsupported-view">
                    <div class="download-card">
                        <div class="download-icon">
                            <i data-lucide="file-down"></i>
                        </div>
                        <h2>Önizleme Desteklenmiyor</h2>
                        <p>
                            <strong>{{ $currentVersion->original_name ?? 'Belge' }}</strong> dosyası tarayıcıda
                            önizlenemez.
                            Lütfen güvenli cihazınıza indirerek görüntüleyin.
                        </p>
                        <a href="{{ route('shared.document.download', ['token' => $share->token]) }}"
                            class="download-btn">
                            Belgeyi İndir
                        </a>
                    </div>
                </div>
            @endif
        </div>
        {{-- YENİ EKLENEN: REVİZYON GEÇMİŞİ BÖLÜMÜ --}}
        <div class="versions-section"
            style="margin-top: 30px; padding: 20px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
            <h3 style="font-size: 1.1rem; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="history"></i> {{ __('Revizyon Geçmişi (Tüm Versiyonlar)') }}
            </h3>

            <div style="display: flex; flex-direction: column; gap: 15px;">
                @foreach ($versions as $version)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #fff; border: 1px solid {{ $version->is_current ? '#10b981' : '#e2e8f0' }}; border-left-width: 4px; border-radius: 8px; transition: all 0.2s;"
                         onmouseover="this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.05)';"
                         onmouseout="this.style.boxShadow='none';">
                        
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <strong style="font-size: 1.1rem; color: var(--text-dark);">v{{ $version->version_number }}</strong>
                                @if ($version->is_current)
                                    <span style="background: #dcfce7; color: #059669; padding: 4px 8px; border-radius: 999px; font-size: 0.7rem; font-weight: 600;">
                                        <i data-lucide="check-circle" style="width: 12px; vertical-align: middle; margin-right: 2px;"></i> {{ __('Aktif Sürüm') }}
                                    </span>
                                @endif
                            </div>
                            
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 8px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                <span style="display: flex; align-items: center; gap: 4px;">
                                    <i data-lucide="calendar" style="width: 14px;"></i> {{ $version->created_at->format('d.m.Y H:i') }}
                                </span>
                                <span>|</span>
                                <span style="display: flex; align-items: center; gap: 4px;">
                                    <i data-lucide="file-text" style="width: 14px;"></i> {{ $version->original_file_name ?? __('Orijinal isim bulunamadı') }}
                                </span>
                            </div>

                            @if ($version->revision_reason)
                                <div style="margin-top: 10px; font-size: 0.85rem; color: #92400e; background: #fffbeb; padding: 8px 12px; border-radius: 6px; border-left: 3px solid #f59e0b;">
                                    <strong style="display: flex; align-items: center; gap: 5px; margin-bottom: 4px;">
                                        <i data-lucide="info" style="width: 14px;"></i> {{ __('Revizyon Notu:') }}
                                    </strong>
                                    {{ $version->revision_reason }}
                                </div>
                            @endif
                        </div>

                        <div style="margin-left: 20px;">
                            {{-- İndirme URL'sine ?v=15 şeklinde parametre basıyoruz --}}
                            <a href="{{ route('shared.document.download', ['token' => $token, 'v' => $version->id]) }}"
                                style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 16px; background: transparent; color: var(--primary); border: 1px solid var(--primary); border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.85rem; transition: .25s ease;"
                                onmouseover="this.style.background='var(--primary-light)';"
                                onmouseout="this.style.background='transparent';">
                                <i data-lucide="download" style="width: 16px;"></i> {{ __(' Versiyon '. $version->version_number . ' İndir') }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="attachments-section"
            style="margin-top: 30px; padding: 20px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
            <h3 style="font-size: 1.1rem; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="paperclip"></i> {{ __('Bu Dokümana Bağlı Ek Dosyalar') }}
            </h3>

            @if ($attachments->isNotEmpty())
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    @foreach ($attachments as $attachment)
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px;">
                            <span style="font-size: 0.9rem;">
                                <i data-lucide="file" style="width: 16px; margin-right: 5px;"></i>
                                {{ $attachment->original_name }}
                            </span>
                            <a href="{{ route('shared.document.download-attachment', ['token' => $token, 'attachment' => $attachment->id]) }}"
                                style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 16px; background: transparent; color: var(--primary); border: 1px solid var(--primary); border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.85rem; transition: .25s ease;"
                                onmouseover="this.style.background='var(--primary-light)';"
                                onmouseout="this.style.background='transparent';">
                                <i data-lucide="download" style="width: 16px;"></i> {{ __('Ek Dosyayı İndir') }}
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <p style="color: var(--text-muted); font-size: 0.9rem;">
                    {{ __('Bu dokümana bağlı ek dosya bulunmuyor.') }}</p>
            @endif
        </div>
    </div>

</body>

</html>
