@extends('layouts.app')

@php
    $user = auth()->user();
    // Kendi ID'si ve Vekili olduğu kişilerin ID'lerini birleştir
    $allIdsToCheck = array_merge([$user->id], $user->getActiveDelegatorIds());

    $canApprove = false;
    $pendingApproval = $document
        ->approvals()
        ->whereIn('user_id', $allIdsToCheck) // Vekalet yetkisiyle ara
        ->where('status', 'pending')
        ->first();

    if ($pendingApproval) {
        $unapprovedPrevious = $document
            ->approvals()
            ->where('step_order', '<', $pendingApproval->step_order)
            ->where('status', '!=', 'approved')
            ->exists();

        $canApprove = !$unapprovedPrevious;
    }
@endphp

@section('content')
    @if (session('success'))
        <div class="alert alert-success mb-20 flex"
            style="align-items: center; gap: 10px; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; padding: 15px; border-radius: 8px;">
            <i data-lucide="check-circle" style="color: var(--success-color);"></i>
            <div><strong>Başarılı!</strong> {{ session('success') }}</div>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger mb-20 flex"
            style="align-items: center; gap: 10px; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; padding: 15px; border-radius: 8px;">
            <i data-lucide="alert-triangle" style="color: var(--danger-color);"></i>
            <div><strong>Hata:</strong> {{ session('error') }}</div>
        </div>
    @endif

    <div class="breadcrumb" style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px;">
        <i data-lucide="folder" style="width: 16px; color: var(--text-muted);"></i>
        <span>{{ $breadcrumb }}</span>
        <span class="separator">/</span>
        <span class="current font-bold" style="color: var(--primary-color);">{{ $document->document_number }}</span>
    </div>

    @if ($document->status_text === 'archived')
        <div class="alert alert-danger flex-between"
            style="background-color: #fef2f2; border-left: 4px solid var(--danger-color); margin-bottom: 20px; padding: 15px;">
            <div style="display: flex; align-items: center; gap: 10px; color: #991b1b;">
                <i data-lucide="archive" style="color: var(--danger-color);"></i>
                <span><strong>SİSTEM ARŞİVİ:</strong> BU BELGE SAKLAMA SÜRESİNİ DOLDURDUĞU İÇİN ARŞİVLENMİŞTİR (SALT
                    OKUNUR). ÜZERİNDE DEĞİŞİKLİK YAPILAMAZ.</span>
            </div>
        </div>
    @endif

    @if ($document->is_locked)
        <div class="alert alert-warning lock-banner flex-between"
            style="background: #fffbeb; border-color: #fcd34d; border-left-width: 4px; padding: 15px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 10px; color: #92400e;">
                <i data-lucide="lock" style="color: var(--warning-color);"></i>
                <span><strong>Dikkat:</strong> Bu belge şu anda
                    <strong>{{ $document->lockedBy?->name ?? 'Bilinmeyen Kullanıcı' }}</strong> tarafından revize edilmek
                    üzere kilitlenmiştir. İşlem bitene kadar salt-okunurdur.</span>
            </div>

            @if ($document->status_text !== 'archived')
                @can('forceUnlock', $document)
                    <form action="{{ route('documents.force-unlock', $document->id) }}" method="POST" class="inline-form"
                        onsubmit="return confirm('Kullanıcının kaydedilmemiş değişiklikleri olabilir. Kilidi zorla açmak istediğinize emin misiniz?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger" style="background: #fff;">
                            <i data-lucide="unlock" style="width: 14px;"></i> Kilidi Zorla Aç
                        </button>
                    </form>
                @endcan
            @endif
        </div>
    @endif

    <div class="page-header flex-between"
        style="background: var(--surface-color); padding: 20px; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--card-shadow); margin-bottom: 25px;">
        <div>
            <h1 class="page-title" style="margin-bottom: 10px; font-size: 1.5rem; color: var(--primary-color);">
                {{ $document->title }}</h1>
            <div class="doc-meta-tags" style="display: flex; gap: 8px;">
                <span class="badge badge-secondary"
                    style="background: var(--bg-color); color: var(--text-color); border: 1px solid var(--border-color);">{{ strtoupper($document->status_text) }}</span>
                <span class="badge badge-warning"
                    style="background: #fef3c7; color: #b45309;">{{ strtoupper($document->privacy_level_text) }}</span>
                @foreach ($document->tags as $tag)
                    <span class="badge badge-secondary"
                        style="background: #f1f5f9; color: #475569;">#{{ $tag->name }}</span>
                @endforeach
            </div>
        </div>

        <div class="action-group" style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end;">
            @if ($document->status_text !== 'archived')
                @if ($canApprove)
                    <button id="openApprovalModal" class="btn btn-warning"
                        style="background: var(--warning-color); color: #fff; border: none;">
                        <i data-lucide="zap" style="width: 18px;"></i> İşlem Yap (Sıra Sizde)
                    </button>
                @endif

                @if (!$document->is_locked)
                    @can('update', $document)
                        <form action="{{ route('documents.checkout', $document->id) }}" method="POST" class="inline-form">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary" style="display: flex; gap: 8px;">
                                <i data-lucide="lock" style="width: 18px;"></i> Revize İçin Kilitle
                            </button>
                        </form>
                    @endcan
                @endif

                @if ($document->is_locked && $document->locked_by === auth()->id())
                    <button type="button" id="openCheckinModal" class="btn btn-success"
                        style="background: var(--success-color); color: #fff;">
                        <i data-lucide="upload-cloud" style="width: 18px;"></i> Yeni Versiyon Yükle (Kilidi Aç)
                    </button>
                @endif

                @if (in_array($document->status_text, ['draft', 'rejected', 'published', 'pending']))
                    @can('update', $document)
                        <button id="openStartWorkflowModal" class="btn btn-outline-secondary">
                            <i data-lucide="play-circle" style="width: 18px;"></i> Onay Akışını Başlat
                        </button>
                    @endcan
                @endif

                @if (in_array($document->category, ['Sözleşme', 'Vekaletname', 'İpotek/Rehin']))
                    @can('update', $document)
                        <button type="button" id="openAssignPhysicalModal" class="btn btn-outline-secondary">
                            <i data-lucide="inbox" style="width: 18px;"></i> Zimmetle / Teslim Et
                        </button>
                    @endcan
                @endif

                @if ($document->delivered_to_user_id === auth()->id() && $document->physical_receipt_status === 'pending')
                    <button type="button" id="openConfirmPhysicalModal" class="btn btn-success pulse-animation">
                        <i data-lucide="check-square" style="width: 18px;"></i> Islak İmzalı Evrakı Teslim Aldım
                    </button>
                @endif
            @endif

            <a href="{{ route('documents.download', $document->id) }}?v={{ $document->currentVersion?->id ?? time() }}&download=1"
                class="btn btn-primary" download>
                <i data-lucide="download" style="width: 18px;"></i> İndir
            </a>
            @can('delete', $document)
                <form action="{{ route('documents.destroy', $document->id) }}" method="POST" class="inline-form"
                    onsubmit="return confirm('DİKKAT: Bu belgeyi sistemden kaldırmak (Soft Delete) istediğinize emin misiniz? Bu işlem geri alınamaz (sadece veritabanından kurtarılabilir).');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger"
                        style="background: var(--danger-color); color: #fff; border: none; display: flex; align-items: center; gap: 8px; padding: 10px 15px; border-radius: 6px; box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.2);">
                        <i data-lucide="trash-2" style="width: 18px;"></i> Belgeyi Sil
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="doc-detail-layout" style="display: grid; grid-template-columns: 250px 1fr; gap: 25px; align-items: start;">

        <aside class="doc-tabs-sidebar card"
            style="padding: 10px 0; background: var(--surface-color); border-radius: var(--border-radius); border: 1px solid var(--border-color);">
            <div class="sidebar-section-title"
                style="padding: 10px 20px; font-size: 0.75rem; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px;">
                BELGE DETAYLARI</div>
            <ul class="vertical-tabs" id="smartTabs" style="list-style: none; padding: 0;">
                <li class="tab-item active" data-target="tab-preview"
                    style="padding: 12px 20px; cursor: pointer; display: flex; align-items: center; gap: 10px; border-left: 3px solid var(--accent-color); background: var(--bg-color); color: var(--accent-color); font-weight: 500;">
                    <i data-lucide="eye" style="width: 18px;"></i> Doküman Önizleme
                </li>
                <li class="tab-item" data-target="tab-info"
                    style="padding: 12px 20px; cursor: pointer; display: flex; align-items: center; gap: 10px; border-left: 3px solid transparent; color: var(--text-muted);">
                    <i data-lucide="info" style="width: 18px;"></i> Doküman Bilgileri
                </li>
                <li class="tab-item" data-target="tab-versions"
                    style="padding: 12px 20px; cursor: pointer; display: flex; align-items: center; gap: 10px; border-left: 3px solid transparent; color: var(--text-muted);">
                    <i data-lucide="history" style="width: 18px;"></i> Revizyon Geçmişi
                </li>
                <li class="tab-item" data-target="tab-approvals"
                    style="padding: 12px 20px; cursor: pointer; display: flex; align-items: center; gap: 10px; border-left: 3px solid transparent; color: var(--text-muted);">
                    <i data-lucide="check-square" style="width: 18px;"></i> İş Akışı Durumu
                </li>

                @if (auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Direktör', 'Müdür']) || $isOwner)
                    <div class="sidebar-section-title mt-20"
                        style="padding: 20px 20px 10px 20px; font-size: 0.75rem; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px;">
                        YÖNETİM & GÜVENLİK</div>
                    <li class="tab-item" data-target="tab-permissions"
                        style="padding: 12px 20px; cursor: pointer; display: flex; align-items: center; gap: 10px; border-left: 3px solid transparent; color: var(--text-muted);">
                        <i data-lucide="shield" style="width: 18px;"></i> Yetki Matrisi
                    </li>
                    <li class="tab-item" data-target="tab-history"
                        style="padding: 12px 20px; cursor: pointer; display: flex; align-items: center; gap: 10px; border-left: 3px solid transparent; color: var(--text-muted);">
                        <i data-lucide="activity" style="width: 18px;"></i> Sistem Logları
                    </li>
                @endif
            </ul>
        </aside>

        <main class="doc-tab-content card relative-container"
            style="padding: 30px; min-height: 600px; background: var(--surface-color); border-radius: var(--border-radius); border: 1px solid var(--border-color);">

            <div id="tab-preview" class="preview-container tab-pane"
                style="background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); height: 800px; display: block; opacity: 1; transition: opacity 0.3s;">

                @php
                    $mimeType = $document->currentVersion->mime_type ?? '';
                    $isPdf = str_contains($mimeType, 'pdf');
                    $isImage = str_starts_with($mimeType, 'image/'); // mime_type 'image/' ile başlıyorsa (jpeg, png, gif vb.)
                @endphp

                @if ($document->currentVersion)
                    @if ($isPdf)
                        <iframe
                            src="{{ route('documents.download', $document->id) }}?v={{ $document->currentVersion?->id }}&t={{ time() }} #toolbar=0&navpanes=0&scrollbar=0&view=FitH"
                            width="100%" height="100%" style="border: none; border-radius: 4px; overflow: hidden;">
                        </iframe>
                    @elseif ($isImage)
                        <div
                            style="display: flex; align-items: center; justify-content: center; height: 100%; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                            <img src="{{ route('documents.download', $document->id) }}?v={{ $document->currentVersion?->id }}&t={{ time() }}"
                                alt="{{ $document->title }}"
                                style="max-width: 100%; max-height: 100%; object-fit: contain; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                        </div>
                    @else
                        <div
                            style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--text-muted);">
                            <i data-lucide="file-x"
                                style="width: 48px; height: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                            <p>Bu dosya formatı ({{ $mimeType }}) tarayıcıda önizlenemez.</p>
                            <a href="{{ route('documents.download', ['document' => $document->id, 'download' => 1]) }}"
                                class="btn btn-primary mt-15">
                                <i data-lucide="download" style="width: 16px; margin-right: 5px;"></i> Dosyayı Bilgisayara
                                İndir
                            </a>
                        </div>
                    @endif
                @else
                    <div
                        style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--text-muted);">
                        <i data-lucide="alert-circle"
                            style="width: 48px; height: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>Bu belgeye ait fiziksel bir dosya bulunamadı.</p>
                    </div>
                @endif
            </div>

            <div id="tab-info" class="tab-pane" style="display: none; opacity: 0; transition: opacity 0.3s;">
                <div class="tab-header" style="margin-bottom: 25px;">
                    <h2 style="font-size: 1.25rem; margin-bottom: 5px;">Doküman Üst Verileri (Kurumsal Kimlik)</h2>
                    <p class="text-muted" style="font-size: 0.9rem;">Bu belgenin sistem üzerindeki kimlik ve imha
                        bilgileri aşağıdadır.</p>
                </div>

                <div class="metadata-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div
                        style="border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; background: #fafafa;">
                        <h3
                            style="font-size: 1rem; color: var(--primary-color); margin-bottom: 15px; border-bottom: 2px solid var(--border-color); padding-bottom: 10px;">
                            Temel Bilgiler</h3>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">Doküman
                                    Adı</span>
                                <span class="font-bold text-right"
                                    style="font-size: 0.95rem;">{{ $document->title }}</span>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">Doküman
                                    Kodu</span>
                                <span class="font-bold text-right"
                                    style="color: var(--accent-color); font-size: 0.95rem;">{{ $document->document_number }}</span>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">Doküman
                                    Tipi</span>
                                <span
                                    class="badge badge-secondary">{{ $document->document_type ?? 'Belirtilmedi' }}</span>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">Standart
                                    Madde No</span>
                                <span style="font-size: 0.95rem;">{{ $document->system_article_no ?? '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        style="border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; background: #fafafa;">
                        <h3
                            style="font-size: 1rem; color: var(--primary-color); margin-bottom: 15px; border-bottom: 2px solid var(--border-color); padding-bottom: 10px;">
                            Organizasyon ve Gizlilik</h3>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">Sahibi
                                    (Hazırlayan)</span>
                                <span
                                    style="font-size: 0.95rem;">{{ $document->currentVersion?->createdBy?->name ?? 'Bilinmiyor' }}</span>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">İlgili
                                    Departman</span>
                                <span
                                    style="font-size: 0.95rem;">{{ $document->relatedDepartment?->name ?? 'Genel (Tüm Şirket)' }}</span>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">Gizlilik
                                    Seviyesi</span>
                                <span class="badge badge-warning">{{ strtoupper($document->privacy_level_text) }}</span>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">Bulunduğu
                                    Dizin</span>
                                <span
                                    style="font-size: 0.8rem; color: var(--text-muted); text-align: right;">{{ $breadcrumb }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        style="border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; grid-column: span 2; background: #fafafa;">
                        <h3
                            style="font-size: 1rem; color: var(--danger-color); margin-bottom: 15px; border-bottom: 2px solid var(--border-color); padding-bottom: 10px;">
                            Saklama ve İmha Politikası</h3>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                            <div
                                style="background: #fff; padding: 15px; border-radius: 6px; border: 1px solid #e5e7eb; border-left: 3px solid var(--primary-color);">
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 5px;">Bölümde
                                    Saklama</div>
                                <div style="font-size: 1.1rem; font-weight: bold; color: var(--text-color);">
                                    {{ $document->department_retention_years }} Yıl</div>
                            </div>
                            <div
                                style="background: #fff; padding: 15px; border-radius: 6px; border: 1px solid #e5e7eb; border-left: 3px solid var(--warning-color);">
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 5px;">Arşivde
                                    Saklama</div>
                                <div style="font-size: 1.1rem; font-weight: bold; color: var(--text-color);">
                                    {{ $document->archive_retention_years }} Yıl</div>
                            </div>
                            <div style="background: #fff; padding: 15px; border-radius: 6px; border: 1px solid #e5e7eb;">
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 5px;">Geçerlilik
                                    Bitiş</div>
                                <div style="font-size: 1.1rem; font-weight: bold; color: var(--text-color);">
                                    {{ $document->expire_at ? \Carbon\Carbon::parse($document->expire_at)->format('d.m.Y') : 'Süresiz' }}
                                </div>
                            </div>
                            <div style="background: #fff; padding: 15px; border-radius: 6px; border: 1px solid #e5e7eb;">
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 5px;">İlk Kayıt
                                    Tarihi</div>
                                <div style="font-size: 1.1rem; font-weight: bold; color: var(--text-color);">
                                    {{ $document->created_at->format('d.m.Y') }}</div>
                            </div>
                        </div>
                    </div>

                    @if (in_array($document->category, ['Sözleşme', 'Vekaletname', 'İpotek/Rehin']))
                        <div
                            style="border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; grid-column: span 2; background: #f8fafc;">
                            <h3
                                style="font-size: 1rem; color: var(--primary-color); margin-bottom: 15px; border-bottom: 2px solid var(--border-color); padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="archive" style="width: 18px;"></i> Fiziksel Arşiv ve Zimmet Durumu
                            </h3>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <p style="margin-bottom: 8px; font-size: 0.95rem;"><strong>Zimmetli Personel:</strong>
                                        {{ $document->deliveredToUser?->name ?? 'Henüz Zimmetlenmedi' }}</p>
                                    <p style="margin-bottom: 8px; font-size: 0.95rem;"><strong>Arşiv Konumu
                                            (Dolap/Raf):</strong> {{ $document->physical_location ?? 'Belirtilmedi' }}</p>
                                    <p
                                        style="margin-bottom: 0; font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
                                        <strong>Teslimat Durumu:</strong>
                                        @if ($document->physical_receipt_status === 'pending')
                                            <span class="badge badge-warning">⏳ Teslim Alma Bekleniyor</span>
                                        @elseif($document->physical_receipt_status === 'received')
                                            <span class="badge badge-success">✅ Teslim Alındı ve Arşivlendi</span>
                                        @else
                                            <span class="badge badge-secondary">Fiziksel İşlem Yok</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div id="tab-versions" class="tab-pane" style="display: none; opacity: 0; transition: opacity 0.3s;">
                <div class="tab-header" style="margin-bottom: 20px;">
                    <h2 style="font-size: 1.25rem;">Revizyon ve Değişiklik Geçmişi</h2>
                </div>

                <div style="display: flex; flex-direction: column; gap: 15px;">
                    @foreach ($document->versions->sortByDesc('created_at') as $version)
                        <div
                            style="border: 1px solid {{ $version->is_current ? 'var(--success-color)' : 'var(--border-color)' }}; border-left-width: 4px; border-radius: 8px; padding: 15px; background: {{ $version->is_current ? '#f0fdf4' : '#fff' }};">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span
                                        style="font-weight: 800; font-size: 1.1rem; color: var(--text-color);">v{{ $version->version_number }}</span>
                                    @if ($version->is_current)
                                        <span class="badge badge-success" style="font-size: 0.7rem;">Aktif Sürüm</span>
                                    @endif
                                </div>
                                <div
                                    style="color: var(--text-muted); font-size: 0.85rem; display: flex; align-items: center; gap: 10px;">
                                    <span style="display: flex; align-items: center; gap: 4px;"><i data-lucide="user"
                                            style="width: 14px;"></i>
                                        {{ $version->createdBy?->name ?? 'Bilinmiyor' }}</span>
                                    <span>|</span>
                                    <span style="display: flex; align-items: center; gap: 4px;"><i data-lucide="clock"
                                            style="width: 14px;"></i>
                                        {{ $version->created_at->format('d.m.Y H:i') }}</span>
                                </div>
                            </div>

                            @if ($version->revision_reason)
                                <div
                                    style="background: var(--surface-color); border: 1px dashed var(--border-color); padding: 12px; border-radius: 6px; font-size: 0.9rem; color: var(--text-color);">
                                    <strong
                                        style="color: var(--warning-color); display: flex; align-items: center; gap: 5px; margin-bottom: 5px;"><i
                                            data-lucide="file-edit" style="width: 14px;"></i>Revizyon
                                        Notu:</strong>
                                    <p style="margin: 0;">{{ $version->revision_reason }}</p>
                                </div>
                            @else
                                <div style="font-size: 0.9rem; color: var(--text-muted); font-style: italic;">
                                    Revizyon notu girilmemiş.
                                </div>
                            @endif

                            <div style="text-align: right; margin-top: 15px;">
                                <a href="{{ route('documents.download', $document->id) }}?v={{ $version->id }}"
                                    class="btn btn-sm btn-outline-primary" style="font-size: 0.8rem;">
                                    <i data-lucide="download" style="width: 14px;"></i> Bu Sürümü İndir
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div id="tab-approvals" class="tab-pane" style="display: none; opacity: 0; transition: opacity 0.3s;">
                <h2 style="font-size: 1.25rem; margin-bottom: 20px;">Onay ve İş Akışı Durumu</h2>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    @forelse($document->approvals as $approval)
                        <div
                            style="display: flex; align-items: center; padding: 15px; border: 1px solid var(--border-color); border-radius: 8px; background: #fff; border-left: 4px solid {{ $approval->status_text === 'approved' ? 'var(--success-color)' : ($approval->status_text === 'rejected' ? 'var(--danger-color)' : 'var(--warning-color)') }};">
                            <div style="width: 80px; font-weight: bold; color: var(--text-muted); font-size: 0.9rem;">Adım
                                {{ $approval->step_order }}</div>
                            <div style="flex: 1; font-weight: 500;">{{ $approval->user?->name ?? 'Bilinmiyor' }}</div>
                            <div style="width: 120px; text-align: right;">
                                @if ($approval->status_text == 'approved')
                                    <span class="badge badge-success">ONAYLANDI</span>
                                @elseif($approval->status_text == 'rejected')
                                    <span class="badge badge-danger">REDDEDİLDİ</span>
                                @else
                                    <span class="badge badge-warning">BEKLİYOR</span>
                                @endif
                            </div>
                        </div>
                        @if ($approval->comment)
                            <div
                                style="margin: -6px 0 10px 80px; font-size: 0.85rem; color: var(--text-muted); font-style: italic; background: #f8fafc; padding: 8px; border-radius: 4px; border: 1px solid #e2e8f0;">
                                <i data-lucide="message-square"
                                    style="width: 12px; display: inline-block; vertical-align: middle;"></i>
                                "{{ $approval->comment }}"
                            </div>
                        @endif
                    @empty
                        <div class="alert alert-info">Bu belge için onay akışı tanımlanmamış. (Doğrudan yayında)</div>
                    @endforelse
                </div>
            </div>

            @if (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || $isOwner)
                <div id="tab-permissions" class="tab-pane" style="display: none; opacity: 0; transition: opacity 0.3s;">
                    <h2 style="font-size: 1.25rem; margin-bottom: 20px;">Erişim ve Yetki Matrisi</h2>

                    <div
                        style="border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; margin-bottom: 30px; background: #fafafa;">
                        <h3
                            style="font-size: 1rem; margin-bottom: 15px; border-bottom: 2px solid var(--border-color); padding-bottom: 10px;">
                            Miras Alınan Sistem Yetkileri</h3>
                        <div class="alert alert-info" style="font-size: 0.9rem; margin-bottom: 15px;">
                            <strong>Mevcut Gizlilik Seviyesi:</strong> {{ strtoupper($document->privacy_level_text) }}<br>
                            Bu seviyedeki dokümanları kurallar gereği sadece aşağıdaki gruplar görebilir:
                        </div>
                        <ul
                            style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px;">
                            <li
                                style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px;">
                                <i data-lucide="crown" style="width: 16px; color: var(--warning-color);"></i> Sistem
                                Yöneticileri (Super Admin, Admin)
                            </li>
                            <li
                                style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px;">
                                <i data-lucide="user-check" style="width: 16px; color: var(--accent-color);"></i> Doküman
                                Sahibi ({{ $document->currentVersion?->createdBy?->name ?? '-' }})
                            </li>
                            @if ($document->privacy_level_text !== 'strictly_confidential')
                                <li
                                    style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px;">
                                    <i data-lucide="git-merge" style="width: 16px; color: var(--success-color);"></i> Onay
                                    Akışındaki Kullanıcılar
                                </li>
                                <li
                                    style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px;">
                                    <i data-lucide="globe" style="width: 16px; color: var(--text-muted);"></i> Diğer
                                    Yetkili Personeller (Klasör iznine bağlı)
                                </li>
                            @endif
                        </ul>
                    </div>

                    <div
                        style="border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; background: #fff;">
                        <h3 style="font-size: 1rem; color: var(--primary-color); margin-bottom: 5px;">Belgeye Özel
                            İstisnalar (Granular Access)</h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">Klasör ve gizlilik
                            yetkilerini <strong>ezerek</strong>, bu belgeye özel erişim izinleri tanımlayabilirsiniz.</p>

                        <form action="{{ route('documents.permissions.store', $document->id) }}" method="POST"
                            style="display: flex; gap: 15px; align-items: flex-end; background: var(--bg-color); padding: 15px; border-radius: 8px; border: 1px dashed #cbd5e1; margin-bottom: 25px;">
                            @csrf
                            <div style="flex: 2;">
                                <label
                                    style="display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 5px;">Kullanıcı
                                    Seçin <span class="text-danger">*</span></label>
                                @php
                                    $allActiveUsers = \App\Models\User::where('is_active', true)
                                        ->where('id', '!=', auth()->id())
                                        ->orderBy('name')
                                        ->get();
                                @endphp
                                <select name="user_id" class="form-control" style="height: 42px;" required>
                                    <option value="">-- Personel Seçin --</option>
                                    @foreach ($allActiveUsers as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}
                                            ({{ $u->roles->pluck('name')->first() ?? 'Personel' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="flex: 1;">
                                <label
                                    style="display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 5px;">Yetki
                                    Seviyesi <span class="text-danger">*</span></label>
                                <select name="access_level" class="form-control" style="height: 42px;" required>
                                    <option value="read">Sadece Okuyabilir</option>
                                    <option value="edit">Düzenleyebilir (Check-in)</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary"
                                style="height: 42px; padding: 0 20px; white-space: nowrap;"><i data-lucide="plus"
                                    style="width: 16px;"></i> Yetki Ekle</button>
                        </form>

                        <div style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: left;">
                                <thead
                                    style="background: var(--bg-color); border-bottom: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">
                                    <tr>
                                        <th style="padding: 12px 15px; font-weight: 600;">Kullanıcı Bilgisi</th>
                                        <th style="padding: 12px 15px; font-weight: 600;">Tanımlanan Yetki Seviyesi</th>
                                        <th style="padding: 12px 15px; font-weight: 600;">Eklenme Tarihi</th>
                                        <th style="padding: 12px 15px; font-weight: 600; text-align: right;">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($document->specificUsers as $specUser)
                                        <tr style="border-bottom: 1px solid var(--border-color);">
                                            <td style="padding: 12px 15px;">
                                                <div style="font-weight: 600; color: var(--text-color);">
                                                    {{ $specUser->name }}</div>
                                                <div style="font-size: 0.8rem; color: var(--text-muted);">
                                                    {{ $specUser->email }}</div>
                                            </td>
                                            <td style="padding: 12px 15px;">
                                                @if ($specUser->pivot->access_level === 'edit')
                                                    <span class="badge badge-warning"
                                                        style="display: inline-flex; align-items: center; gap: 4px;"><i
                                                            data-lucide="edit-2" style="width: 12px;"></i> Okur ve
                                                        Düzenler</span>
                                                @else
                                                    <span class="badge badge-secondary"
                                                        style="display: inline-flex; align-items: center; gap: 4px;"><i
                                                            data-lucide="eye" style="width: 12px;"></i> Sadece Okur</span>
                                                @endif
                                            </td>
                                            <td style="padding: 12px 15px; color: var(--text-muted);">
                                                {{ $specUser->pivot->created_at->format('d.m.Y H:i') }}</td>
                                            <td style="padding: 12px 15px; text-align: right;">
                                                <form
                                                    action="{{ route('documents.permissions.destroy', [$document->id, $specUser->id]) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Bu özel yetkiyi kaldırmak istediğinize emin misiniz?');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        style="padding: 6px 10px; display: inline-flex; align-items: center; gap: 4px;"><i
                                                            data-lucide="trash-2" style="width: 14px;"></i>
                                                        Kaldır</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4"
                                                style="padding: 30px; text-align: center; color: var(--text-muted);">
                                                Bu belge için tanımlanmış özel bir istisna yetkisi bulunmuyor.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if (auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Direktör', 'Müdür']) || $isOwner)
                <div id="tab-history" class="tab-pane" style="display: none; opacity: 0; transition: opacity 0.3s;">
                    <h2 style="font-size: 1.25rem; margin-bottom: 20px;">Sistem ve Görüntüleme Logları</h2>

                    <h3
                        style="font-size: 1rem; color: var(--primary-color); margin-bottom: 15px; border-bottom: 2px solid var(--border-color); padding-bottom: 10px;">
                        İşlem Logları (Audit)</h3>

                    @php
                        // --- 1. OLAY ÇEVİRİ SÖZLÜĞÜ ---
                        $eventDict = [
                            'document_created' => 'Yeni Belge Yüklendi',
                            'document_published' => 'Yayına Alındı',
                            'document_updated' => 'Belge Güncellendi',
                            'document_approved' => 'Akış Onaylandı',
                            'document_rejected' => 'Akış Reddedildi',
                            'workflow_started' => 'Onay Akışı Başlatıldı',
                            'document_archived' => 'Arşive Kaldırıldı',
                            'document_deleted' => 'Sistemden Silindi',
                            'physical_receipt' => 'Fiziksel Teslimat',
                            // YENİ EKLENEN SİSTEM LOGLARI:
                            'downloaded' => 'Belge İndirildi',
                            'locked_for_revision' => 'Revizyon İçin Kilitlendi',
                            'force_unlocked_by_admin' => 'Yönetici Kilidi Açtı',
                            'checked_in' => 'Yeni Versiyon Yüklendi',
                            'checked_out' => 'Düzenleme İçin Alındı',
                        ];

                        // --- 2. DİNAMİK RENK SÖZLÜĞÜ (Spagetti if-else yerine) ---
                        $colorDict = [
                            'document_created' => 'badge-primary',
                            'document_published' => 'badge-success',
                            'document_updated' => 'badge-warning',
                            'document_approved' => 'badge-success',
                            'document_rejected' => 'badge-danger',
                            'document_deleted' => 'badge-danger',
                            'downloaded' => 'badge-info',
                            'locked_for_revision' => 'badge-danger',
                            'force_unlocked_by_admin' => 'badge-warning',
                            'checked_in' => 'badge-success',
                        ];

                        // --- 3. ALAN (FIELD) ÇEVİRİ SÖZLÜĞÜ ---
                        $fieldDict = [
                            'title' => 'Belge Başlığı',
                            'document_number' => 'Belge No',
                            'status' => 'Statü',
                            'privacy_level' => 'Gizlilik Seviyesi',
                            'category' => 'Kategori',
                            'folder_id' => 'Klasör (ID)',
                            'approvers_count' => 'Onaycı Sayısı',
                            'is_locked' => 'Kilit Durumu',
                            'locked_by' => 'Kilitleyen (ID)',
                            'physical_location' => 'Fiziksel Konum',
                            'delivered_to_user_id' => 'Zimmetli Personel (ID)',
                            'physical_receipt_status' => 'Fiziksel Teslimat Durumu',
                            'contract_party' => 'Sözleşme Tarafı',
                            // YENİ EKLENEN VERİ TABANI ALANLARI:
                            'version' => 'İşlem Gören Sürüm',
                            'revision_reason' => 'Revizyon Notu',
                            'file_path' => 'Dosya Yolu',
                        ];
                    @endphp

                    <div
                        style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; margin-bottom: 30px;">
                        <div style="max-height: 350px; overflow-y: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
                                <thead
                                    style="background: var(--bg-color); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 10;">
                                    <tr>
                                        <th style="padding: 10px 15px; color: var(--text-muted); font-weight: 600;">Tarih
                                        </th>
                                        <th style="padding: 10px 15px; color: var(--text-muted); font-weight: 600;">
                                            Kullanıcı</th>
                                        <th style="padding: 10px 15px; color: var(--text-muted); font-weight: 600;">İşlem
                                            (Olay)</th>
                                        <th style="padding: 10px 15px; color: var(--text-muted); font-weight: 600;">
                                            Detaylar (Değişen Alanlar)</th>
                                        <th style="padding: 10px 15px; color: var(--text-muted); font-weight: 600;">IP
                                            Adresi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($auditLogs as $log)
                                        <tr style="border-bottom: 1px solid var(--border-color);">
                                            <td style="padding: 12px 15px; white-space: nowrap; color: var(--text-color);">
                                                {{ $log->created_at->format('d.m.Y H:i:s') }}</td>
                                            <td style="padding: 12px 15px; font-weight: 500;">
                                                {{ $log->user?->name ?? 'Sistem' }}</td>
                                            <td style="padding: 12px 15px;">
                                                <span class="badge {{ $colorDict[$log->event] ?? 'badge-secondary' }}"
                                                    style="font-size: 0.7rem; padding: 4px 8px;">
                                                    {{ $eventDict[$log->event] ?? strtoupper($log->event) }}
                                                </span>
                                            </td>
                                            <td style="padding: 12px 15px;">
                                                @if (!empty($log->old_values) || !empty($log->new_values))
                                                    @php
                                                        $olds = is_string($log->old_values)
                                                            ? json_decode($log->old_values, true)
                                                            : (array) ($log->old_values ?? []);
                                                        $news = is_string($log->new_values)
                                                            ? json_decode($log->new_values, true)
                                                            : (array) ($log->new_values ?? []);
                                                    @endphp

                                                    @if (count($news) > 0)
                                                        <ul
                                                            style="margin: 0; padding-left: 0; list-style-type: none; display: flex; flex-direction: column; gap: 4px;">
                                                            @foreach ($news as $field => $newValue)
                                                                @php
                                                                    $translatedField =
                                                                        $fieldDict[$field] ?? strtoupper($field);
                                                                    $oldValue = $olds[$field] ?? 'Boş';
                                                                    $oldValueStr =
                                                                        is_array($oldValue) || is_object($oldValue)
                                                                            ? json_encode(
                                                                                $oldValue,
                                                                                JSON_UNESCAPED_UNICODE,
                                                                            )
                                                                            : (string) $oldValue;
                                                                    $newValueStr =
                                                                        is_array($newValue) || is_object($newValue)
                                                                            ? json_encode(
                                                                                $newValue,
                                                                                JSON_UNESCAPED_UNICODE,
                                                                            )
                                                                            : (string) $newValue;

                                                                    $valDict = [
                                                                        // Statüler
                                                                        'pending' => 'Bekliyor',
                                                                        'pending_approval' => 'Onay Bekliyor', // <-- Sorunu çözen anahtar!
                                                                        'published' => 'Yayında',
                                                                        'draft' => 'Taslak',
                                                                        'rejected' => 'Reddedildi',
                                                                        'approved' => 'Onaylandı',
                                                                        'archived' => 'Arşivlendi',

                                                                        // Gizlilik Seviyeleri (Potansiyel sızıntıları önlemek için)
                                                                        'public' => 'Herkese Açık',
                                                                        'confidential' => 'Hizmete Özel',
                                                                        'strictly_confidential' => 'Gizli',

                                                                        // Boolean / Kilit Değerleri
                                                                        '1' => 'Evet / Aktif',
                                                                        '0' => 'Hayır / Pasif',
                                                                    ];
                                                                    $oldPrint = \Illuminate\Support\Str::limit(
                                                                        $valDict[$oldValueStr] ?? $oldValueStr,
                                                                        30,
                                                                    );
                                                                    $newPrint = \Illuminate\Support\Str::limit(
                                                                        $valDict[$newValueStr] ?? $newValueStr,
                                                                        30,
                                                                    );
                                                                @endphp
                                                                <li
                                                                    style="padding: 6px; background: #f8fafc; border-radius: 4px; border-left: 3px solid #cbd5e1; font-size: 0.8rem;">
                                                                    <strong
                                                                        style="color: #475569;">{{ $translatedField }}:</strong>
                                                                    <span
                                                                        style="color: var(--danger-color); text-decoration: line-through; margin-left: 5px;">{{ $oldPrint }}</span>
                                                                    <i data-lucide="arrow-right"
                                                                        style="width: 12px; margin: 0 4px; vertical-align: middle; color: var(--text-muted);"></i>
                                                                    <span
                                                                        style="color: var(--success-color); font-weight: bold;">{{ $newPrint }}</span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td style="padding: 12px 15px; color: var(--text-muted); font-size: 0.75rem;">
                                                {{ $log->ip_address }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <h3
                        style="font-size: 1rem; color: var(--primary-color); margin-bottom: 15px; border-bottom: 2px solid var(--border-color); padding-bottom: 10px;">
                        Okuma/İnceleme Süreleri </h3>
                    <div style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
                        <div style="max-height: 250px; overflow-y: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
                                <thead
                                    style="background: var(--bg-color); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 10;">
                                    <tr>
                                        <th style="padding: 10px 15px; color: var(--text-muted); font-weight: 600;">Tarih
                                        </th>
                                        <th style="padding: 10px 15px; color: var(--text-muted); font-weight: 600;">
                                            Kullanıcı</th>
                                        <th style="padding: 10px 15px; color: var(--text-muted); font-weight: 600;">Süre
                                            (Saniye)</th>
                                        <th style="padding: 10px 15px; color: var(--text-muted); font-weight: 600;">Kalite
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($readLogs as $rLog)
                                        <tr style="border-bottom: 1px solid var(--border-color);">
                                            <td style="padding: 12px 15px; color: var(--text-color);">
                                                {{ $rLog->created_at->format('d.m.Y H:i') }}</td>
                                            <td style="padding: 12px 15px; font-weight: 500;">
                                                {{ $rLog->user?->name ?? 'Misafir' }}</td>
                                            <td style="padding: 12px 15px; font-weight: bold;">
                                                {{ $rLog->duration_seconds }} sn</td>
                                            <td style="padding: 12px 15px;">
                                                @if ($rLog->duration_seconds < 5)
                                                    <span class="badge badge-danger" style="font-size: 0.7rem;">Çok
                                                        Kısa</span>
                                                @elseif($rLog->duration_seconds < 30)
                                                    <span class="badge badge-warning" style="font-size: 0.7rem;">Hızlı
                                                        İnceleme</span>
                                                @else
                                                    <span class="badge badge-success" style="font-size: 0.7rem;">Detaylı
                                                        Okuma</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

        </main>
    </div>

    <div id="approvalModal" class="modal-overlay"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div class="modal-content"
            style="background: #fff; padding: 30px; border-radius: 12px; width: 100%; max-width: 450px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="font-size: 1.25rem;">Kararınızı Belirtin</h2>
                <button type="button" class="close-modal"
                    style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
            </div>
            <form id="approvalForm" method="POST" action="{{ route('documents.approve', $document->id) }}">
                @csrf
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">Lütfen bu belge için
                    kararınızı belirtin.</p>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <label
                        style="border: 2px solid var(--border-color); border-radius: 8px; padding: 20px 10px; text-align: center; cursor: pointer; transition: all 0.2s;"
                        class="decision-label" data-type="approve">
                        <input type="radio" name="decision" value="approve" required style="display: none;">
                        <i data-lucide="check-circle"
                            style="color: var(--text-muted); margin: 0 auto 10px; width: 32px; height: 32px; display: block;"
                            class="decision-icon"></i>
                        <div style="font-weight: 600; color: var(--text-muted);" class="decision-text">Onayla</div>
                    </label>
                    <label
                        style="border: 2px solid var(--border-color); border-radius: 8px; padding: 20px 10px; text-align: center; cursor: pointer; transition: all 0.2s;"
                        class="decision-label" data-type="reject">
                        <input type="radio" name="decision" value="reject" required style="display: none;">
                        <i data-lucide="x-circle"
                            style="color: var(--text-muted); margin: 0 auto 10px; width: 32px; height: 32px; display: block;"
                            class="decision-icon"></i>
                        <div style="font-weight: 600; color: var(--text-muted);" class="decision-text">Reddet</div>
                    </label>
                </div>

                <div id="rejectCommentWrapper" style="display: none; margin-bottom: 20px;">
                    <label
                        style="display: block; font-size: 0.9rem; margin-bottom: 5px; color: var(--danger-color); font-weight: 500;">Red
                        Sebebi (Zorunlu)</label>
                    <textarea name="comment" id="rejectComment" class="form-control" rows="3"
                        style="width: 100%; border: 1px solid var(--border-color); border-radius: 6px; padding: 12px; font-family: inherit;"
                        placeholder="Lütfen reddetme sebebinizi açıklayın..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary"
                    style="width: 100%; padding: 12px; justify-content: center; font-size: 1rem;">Kararımı İlet</button>
            </form>
        </div>
    </div>

    <div id="checkinModal" class="modal-overlay"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div class="modal-content"
            style="background: #fff; padding: 30px; border-radius: 12px; width: 100%; max-width: 500px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="font-size: 1.25rem;">Yeni Versiyon Yükle (Check-in)</h2>
                <button type="button" class="close-modal checkin-close"
                    style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
            </div>
            <form id="checkinForm" action="{{ route('documents.checkin', $document->id) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div id="checkinError" class="alert alert-danger"
                    style="display: none; margin-bottom: 15px; padding: 10px; border-radius: 6px; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.9rem; font-weight: 500; margin-bottom: 8px;">Revize Edilmiş
                        Dosya <span style="color: var(--danger-color);">*</span></label>
                    <div style="position: relative; width: 100%;">
                        <input type="file" name="file" id="checkinFile" required
                            style="position: absolute; margin: 0; padding: 0; width: 100%; height: 100%; outline: none; opacity: 0; cursor: pointer; z-index: 2;">
                        <label for="checkinFile"
                            style="display: flex; align-items: center; justify-content: center; padding: 20px; background-color: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 8px; color: var(--text-muted); font-size: 1rem; transition: all 0.3s ease; z-index: 1;">
                            <i data-lucide="file-up"
                                style="width: 24px; height: 24px; margin-right: 10px; color: var(--accent-color);"></i>
                            <span id="checkin-file-name">Dosya Seçin veya Sürükleyin</span>
                        </label>
                    </div>
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 0.9rem; font-weight: 500; margin-bottom: 8px;">Revizyon Nedeni
                        / Değişiklikler (Opsiyonel)</label>
                    <textarea name="revision_reason" rows="3"
                        style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-family: inherit;"
                        placeholder="Örn: Madde 4 düzeltildi..."></textarea>
                </div>

                <button type="submit" id="checkinSubmitBtn" class="btn btn-success"
                    style="width: 100%; padding: 12px; justify-content: center; font-size: 1rem;">
                    <i data-lucide="unlock" style="width: 18px;"></i> Yükle ve Kilidi Kaldır
                </button>
            </form>
        </div>
    </div>

    <div id="assignPhysicalModal" class="modal-overlay"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div class="modal-content"
            style="background: #fff; padding: 30px; border-radius: 12px; width: 100%; max-width: 450px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="font-size: 1.25rem;">Fiziksel Kopyayı Teslim Et</h2>
                <button type="button" class="close-modal" id="closeAssignModal"
                    style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
            </div>
            <form action="{{ route('documents.assign-physical', $document->id) }}" method="POST">
                @csrf
                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 0.9rem; font-weight: 500; margin-bottom: 8px;">Teslim Edilecek
                        Personel <span style="color: var(--danger-color);">*</span></label>
                    @php $allUsers = \App\Models\User::where('is_active', true)->orderBy('name')->get(); @endphp
                    <select name="delivered_to_user_id" class="form-control"
                        style="width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px;"
                        required>
                        <option value="">-- Personel Seçin --</option>
                        @foreach ($allUsers as $u)
                            <option value="{{ $u->id }}"
                                {{ $document->delivered_to_user_id == $u->id ? 'selected' : '' }}>{{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"
                    style="width: 100%; padding: 12px; justify-content: center; font-size: 1rem;">Zimmet İşlemini
                    Başlat</button>
            </form>
        </div>
    </div>

    <div id="confirmPhysicalModal" class="modal-overlay"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div class="modal-content"
            style="background: #fff; padding: 30px; border-radius: 12px; width: 100%; max-width: 500px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="font-size: 1.25rem;">Fiziksel Evrakı Teslim Al</h2>
                <button type="button" class="close-modal" id="closeConfirmModal"
                    style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
            </div>
            <form action="{{ route('documents.confirm-physical', $document->id) }}" method="POST">
                @csrf
                <div
                    style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;">
                    <i data-lucide="info"
                        style="width: 16px; display: inline-block; vertical-align: text-bottom; margin-right: 5px;"></i>
                    Evrakın <strong>ıslak imzalı orijinal kopyasını</strong> fiziksel olarak teslim aldığınızı
                    onaylıyorsunuz. Lütfen evrakı koyduğunuz fiziksel konumu (Dolap, Raf, Klasör vb.) belirtin.
                </div>
                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 0.9rem; font-weight: 500; margin-bottom: 8px;">Arşiv Konumu
                        <span style="color: var(--danger-color);">*</span></label>
                    <input type="text" name="physical_location" class="form-control"
                        style="width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px;"
                        placeholder="Örn: Arşiv Odası B, Çelik Kasa 4, Mavi Klasör" required>
                </div>
                <button type="submit" class="btn btn-success"
                    style="width: 100%; padding: 12px; justify-content: center; font-size: 1rem;">
                    <i data-lucide="check-square" style="width: 18px;"></i> Teslim Aldım ve Arşive Kaldırdım
                </button>
            </form>
        </div>
    </div>

    <div id="startWorkflowModal" class="modal-overlay"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div class="modal-content"
            style="background: #fff; padding: 30px; border-radius: 12px; width: 100%; max-width: 600px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="font-size: 1.25rem;">Onay Akışını Başlat</h2>
                <button type="button" class="close-modal" id="closeStartWorkflowModal"
                    style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
            </div>
            <form action="{{ route('documents.workflow.start', $document->id) }}" method="POST">
                @csrf
                <div
                    style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem;">
                    Bu belgeyi yayınlamadan önce onaylaması gereken kişileri (hiyerarşik sırayla) seçin. Aynı sırayı
                    verdiğiniz kişiler <strong>"Paralel Onaycı"</strong> olur.
                </div>

                <div id="approversContainer" style="display: flex; flex-direction: column; gap: 15px;">
                    <div class="approver-row"
                        style="display: flex; gap: 15px; align-items: flex-end; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <div style="flex: 2;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 5px;">1.
                                Onaycı <span style="color: var(--danger-color);">*</span></label>
                            @php
                                $allUsersWf = \App\Models\User::where('is_active', true)
                                    ->where('id', '!=', auth()->id())
                                    ->orderBy('name')
                                    ->get();
                            @endphp
                            <select name="approvers[0][user_id]" class="form-control"
                                style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;"
                                required>
                                <option value="">-- Kullanıcı Seçin --</option>
                                @foreach ($allUsersWf as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}
                                        ({{ $u->roles->pluck('name')->first() ?? 'Personel' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 5px;">Sıra
                                (Adım) <span style="color: var(--danger-color);">*</span></label>
                            <input type="number" name="approvers[0][step_order]" value="1" min="1"
                                class="form-control"
                                style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;"
                                required>
                        </div>
                    </div>
                </div>

                <button type="button" id="addApproverBtn" class="btn btn-sm btn-outline-secondary"
                    style="margin-top: 15px; width: 100%; border-style: dashed;">
                    <i data-lucide="plus" style="width: 14px;"></i> Yeni Onaycı Ekle
                </button>

                <div style="margin-top: 25px; text-align: right;">
                    <button type="submit" class="btn btn-success" style="padding: 12px 25px; font-size: 1.05rem;">
                        <i data-lucide="rocket" style="width: 18px;"></i> Akışı Başlat
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- LUCIDE İKONLARI ---
            lucide.createIcons();

            // --- TAB KONTROLÜ (SMART TABS) ---
            const tabs = document.querySelectorAll('.tab-item');
            const panes = document.querySelectorAll('.tab-pane');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    tabs.forEach(t => {
                        t.classList.remove('active');
                        t.style.borderLeftColor = 'transparent';
                        t.style.background = 'transparent';
                        t.style.color = 'var(--text-muted)';
                        t.style.fontWeight = 'normal';
                    });

                    panes.forEach(p => {
                        p.style.display = 'none';
                        p.style.opacity = '0';
                    });

                    this.classList.add('active');
                    this.style.borderLeftColor = 'var(--accent-color)';
                    this.style.background = 'var(--bg-color)';
                    this.style.color = 'var(--accent-color)';
                    this.style.fontWeight = '500';

                    const targetId = this.getAttribute('data-target');
                    const targetPane = document.getElementById(targetId);

                    if (targetPane) {
                        targetPane.style.display = 'block';
                        setTimeout(() => {
                            targetPane.style.opacity = '1';
                        }, 10);
                    }
                });
            });

            if (tabs.length > 0) {
                tabs[0].click(); // Sayfa yüklendiğinde ilk tabı aç
            }

            // --- MODAL YARDIMCI FONKSİYONU ---
            const setupModal = (btnId, modalId, closeBtnClass = '.close-modal') => {
                const btn = document.getElementById(btnId);
                const modal = document.getElementById(modalId);
                if (btn && modal) {
                    btn.addEventListener('click', () => {
                        modal.style.display = 'flex';
                    });
                    const closeBtn = modal.querySelector(closeBtnClass);
                    if (closeBtn) {
                        closeBtn.addEventListener('click', () => {
                            modal.style.display = 'none';
                        });
                    }
                    // Dışarı tıklayınca kapatma
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) modal.style.display = 'none';
                    });
                }
            };

            // Tüm modalları init et
            setupModal('openApprovalModal', 'approvalModal');
            setupModal('openCheckinModal', 'checkinModal', '.checkin-close');
            setupModal('openStartWorkflowModal', 'startWorkflowModal', '#closeStartWorkflowModal');
            setupModal('openAssignPhysicalModal', 'assignPhysicalModal', '#closeAssignModal');
            setupModal('openConfirmPhysicalModal', 'confirmPhysicalModal', '#closeConfirmModal');

            // --- ONAY/RED SEBEBİ UI KONTROLÜ ---
            const radios = document.querySelectorAll('input[name="decision"]');
            const commentWrapper = document.getElementById('rejectCommentWrapper');
            const commentInput = document.getElementById('rejectComment');
            const approvalForm = document.getElementById('approvalForm');
            const decisionLabels = document.querySelectorAll('.decision-label');

            radios.forEach(radio => {
                radio.addEventListener('change', (e) => {
                    // UI sıfırlama
                    decisionLabels.forEach(label => {
                        label.style.borderColor = 'var(--border-color)';
                        label.style.background = '#fff';
                        label.querySelector('.decision-icon').style.color =
                            'var(--text-muted)';
                        label.querySelector('.decision-text').style.color =
                            'var(--text-muted)';
                    });

                    // Seçileni renklendirme
                    const selectedLabel = e.target.closest('label');
                    if (e.target.value === 'approve') {
                        selectedLabel.style.borderColor = 'var(--success-color)';
                        selectedLabel.style.background = '#f0fdf4';
                        selectedLabel.querySelector('.decision-icon').style.color =
                            'var(--success-color)';
                        selectedLabel.querySelector('.decision-text').style.color =
                            'var(--success-color)';

                        commentWrapper.style.display = 'none';
                        commentInput.required = false;
                        if (approvalForm) approvalForm.action =
                            '{{ route('documents.approve', $document->id ?? 0) }}';
                    } else {
                        selectedLabel.style.borderColor = 'var(--danger-color)';
                        selectedLabel.style.background = '#fef2f2';
                        selectedLabel.querySelector('.decision-icon').style.color =
                            'var(--danger-color)';
                        selectedLabel.querySelector('.decision-text').style.color =
                            'var(--danger-color)';

                        commentWrapper.style.display = 'block';
                        commentInput.required = true;
                        if (approvalForm) approvalForm.action =
                            '{{ route('documents.reject', $document->id ?? 0) }}';
                    }
                });
            });

            // --- CHECK-IN DOSYA İSMİ GÖSTERİMİ ---
            const checkinFileInput = document.getElementById('checkinFile');
            const checkinFileName = document.getElementById('checkin-file-name');
            if (checkinFileInput && checkinFileName) {
                checkinFileInput.addEventListener('change', function(e) {
                    if (e.target.files.length > 0) {
                        checkinFileName.textContent = e.target.files[0].name;
                        checkinFileName.style.color = 'var(--success-color)';
                        checkinFileName.style.fontWeight = 'bold';
                    } else {
                        checkinFileName.textContent = 'Dosya Seçin veya Sürükleyin';
                        checkinFileName.style.color = 'var(--text-muted)';
                        checkinFileName.style.fontWeight = 'normal';
                    }
                });
            }

            // --- CHECK-IN AJAX (Sessiz Hata Yakalama) ---
            const checkinForm = document.getElementById('checkinForm');
            const checkinError = document.getElementById('checkinError');
            const checkinSubmitBtn = document.getElementById('checkinSubmitBtn');

            if (checkinForm) {
                checkinForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    checkinError.style.display = 'none';
                    checkinError.textContent = '';
                    checkinSubmitBtn.disabled = true;
                    checkinSubmitBtn.innerHTML =
                        '<i data-lucide="loader" class="spin" style="width: 18px;"></i> Yükleniyor...';
                    lucide.createIcons(); // Spini çizmek için

                    const formData = new FormData(this);

                    fetch(this.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                            },
                            body: formData
                        })
                        .then(async response => {
                            const data = await response.json();
                            if (!response.ok) {
                                let errorMsg = data.message || 'Bilinmeyen bir hata oluştu.';
                                if (response.status === 422 && data.errors) {
                                    errorMsg = Object.values(data.errors)[0][0];
                                }
                                throw new Error(errorMsg);
                            }
                            return data;
                        })
                        .then(data => {
                            window.location.reload();
                        })
                        .catch(error => {
                            checkinError.style.display = 'block';
                            checkinError.textContent = '⚠️ ' + error.message;
                            checkinSubmitBtn.disabled = false;
                            checkinSubmitBtn.innerHTML =
                                '<i data-lucide="unlock" style="width: 18px;"></i> Yükle ve Kilidi Kaldır';
                            lucide.createIcons();
                        });
                });
            }

            // --- İŞ AKIŞI DİNAMİK SATIR EKLEME (JS) ---
            let approverIndex = 1;
            const addApproverBtn = document.getElementById('addApproverBtn');
            const approversContainer = document.getElementById('approversContainer');

            if (addApproverBtn && approversContainer) {
                // İlk select box'ın innerHTML'ini kopyalayalım (Tüm kullanıcılar)
                const firstSelect = approversContainer.querySelector('select');
                const selectOptions = firstSelect ? firstSelect.innerHTML : '';

                addApproverBtn.addEventListener('click', function() {
                    const nextStepOrder = approverIndex + 1;
                    const newRow = document.createElement('div');
                    newRow.className = 'approver-row';
                    newRow.style.cssText =
                        'display: flex; gap: 15px; align-items: flex-end; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 15px;';
                    newRow.innerHTML = `
                        <div style="flex: 2;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 5px;">${nextStepOrder}. Onaycı <span style="color: var(--danger-color);">*</span></label>
                            <select name="approvers[${approverIndex}][user_id]" class="form-control" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;" required>
                                ${selectOptions}
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label style="display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 5px;">Sıra (Adım) <span style="color: var(--danger-color);">*</span></label>
                            <input type="number" name="approvers[${approverIndex}][step_order]" value="${nextStepOrder}" min="1" class="form-control" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;" required>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-approver-btn" style="padding: 10px 15px; height: 42px;">
                            <i data-lucide="trash-2" style="width: 16px;"></i>
                        </button>
                    `;
                    approversContainer.appendChild(newRow);
                    lucide.createIcons(); // Yeni eklenen ikonları çiz
                    approverIndex++;

                    // Silme butonuna event ekle
                    newRow.querySelector('.remove-approver-btn').addEventListener('click', function() {
                        newRow.remove();
                    });
                });
            }

            // --- SAYFADA KALMA SÜRESİ (BEACON API TRACKING) ---
            let entryTime = Date.now();
            let isTracking = true;

            const sendTimeLog = () => {
                if (!isTracking) return;
                const duration = Math.floor((Date.now() - entryTime) / 1000);

                if (duration > 2) {
                    const url = '{{ route('documents.log-time', $document->id) }}';
                    const data = JSON.stringify({
                        duration: duration,
                        _token: '{{ csrf_token() }}'
                    });

                    // Tarayıcı kapanırken verinin gitmesini garantilemek için sendBeacon kullanılır
                    if (navigator.sendBeacon) {
                        const blob = new Blob([data], {
                            type: 'application/json'
                        });
                        navigator.sendBeacon(url, blob);
                    } else {
                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: data,
                            keepalive: true
                        }).catch(console.error);
                    }
                }
                isTracking = false;
            };

            window.addEventListener('beforeunload', sendTimeLog);

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    sendTimeLog();
                } else {
                    entryTime = Date.now();
                    isTracking = true;
                }
            });

        });
    </script>

    <style>
        /* Spin animasyonu (Ajax yüklenirken) */
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .spin {
            animation: spin 1s linear infinite;
        }
    </style>
@endpush
