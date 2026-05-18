<div class="card"
    style="border-radius: 12px; overflow: hidden; border: 1px solid var(--border-color); background: #fff;">
    <div class="table-responsive">
        <table class="table modern-table" style="margin: 0; width: 100%; text-align: left; border-collapse: collapse;">
            <thead
                style="background: #f8fafc; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; border-bottom: 2px solid var(--border-color);">
                <tr>
                    <th style="padding: 15px 20px;">{{ __('Doküman Bilgisi') }}</th>
                    <th style="padding: 15px 20px;">{{ __('Doküman Tipi') }}</th>
                    <th style="padding: 15px 20px;">{{ __('Gizlilik ve Konum') }}</th>
                    <th style="padding: 15px 20px;">{{ __('Statü') }}</th>
                    <th style="padding: 15px 20px;">{{ __('Tarih') }}</th>
                    <th class="text-right" style="padding: 15px 20px; text-align: right;">{{ __('İşlemler') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $doc)
                    <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">

                        {{-- 1. DOKÜMAN BİLGİSİ (Numara, Başlık, Yükleyen) --}}
                        <td style="padding: 15px 20px; vertical-align: top;">
                            <div
                                style="font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 6px; font-size: 0.95rem;">
                                {{ $doc->document_number }}
                                @if ($doc->requires_vault)
                                    <i data-lucide="shield-check" style="width: 16px; color: var(--warning-color);"
                                        title="{{ __('Kasa Korumalı') }}"></i>
                                @endif
                                @if ($doc->is_locked)
                                    <i data-lucide="lock" style="width: 14px; color: var(--danger-color);"
                                        title="{{ __('Revizyon İçin Kilitli') }}"></i>
                                @endif
                            </div>
                            <div
                                style="font-size: 1.05rem; font-weight: 600; margin: 4px 0 6px 0; color: var(--text-color);">
                                <a href="{{ route('documents.show', $doc->id) }}"
                                    style="color: inherit; text-decoration: none; transition: color 0.2s;"
                                    onmouseover="this.style.color='var(--accent-color)'"
                                    onmouseout="this.style.color='inherit'">
                                    {{ $doc->title }}
                                </a>
                            </div>
                            <div
                                style="font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px;">
                                <i data-lucide="user" style="width: 14px; height: 14px;"></i>
                                @if ($doc->currentVersion && $doc->currentVersion->createdBy)
                                    <a href="{{ route('profile.show', $doc->currentVersion->createdBy->id) }}"
                                        target="_blank" rel="noopener noreferrer"
                                        style="color: var(--accent-color); text-decoration: none; font-weight: 600; transition: opacity 0.2s;"
                                        onmouseover="this.style.textDecoration='underline'"
                                        onmouseout="this.style.textDecoration='none'">
                                        {{ $doc->currentVersion->createdBy->name }}
                                    </a>
                                @else
                                    <span style="font-style: italic;">{{ __('Sistem') }}</span>
                                @endif
                            </div>
                        </td>

                        {{-- 2. DOKÜMAN TİPİ --}}
                        <td style="padding: 15px 20px; vertical-align: top;">
                            <span class="badge badge-secondary"
                                style="font-size: 0.75rem; padding: 4px 8px; border-radius: 6px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; display: inline-block;">
                                {{ $doc->documentType?->name ? __($doc->documentType->name) : __('Genel Doküman') }}
                            </span>
                        </td>

                        {{-- 3. GİZLİLİK VE KONUM (BREADCRUMB) --}}
                        <td style="padding: 15px 20px; vertical-align: top;">
                            <span class="badge {{ $doc->privacy_color }}"
                                style="margin-bottom: 8px; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                {{ mb_strtoupper(__($doc->privacy_level_text)) }}
                            </span>

                            {{-- MODERN KONUM ROZETİ (LOCATION BADGE) --}}
                            <div style="margin-top: 4px;">
                                @if ($doc->folder)
                                    <a href="{{ route('folders.show', $doc->folder_id) }}"
                                        title="{{ __('Klasöre Git') }}"
                                        style="display: inline-flex; align-items: center; gap: 6px; background: #eef2ff; color: #4f46e5; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-decoration: none; border: 1px solid #c7d2fe; transition: all 0.2s;"
                                        onmouseover="this.style.background='#e0e7ff'; this.style.borderColor='#a5b4fc';"
                                        onmouseout="this.style.background='#eef2ff'; this.style.borderColor='#c7d2fe';">
                                        <i data-lucide="folder-tree" style="width: 14px; height: 14px;"></i>
                                        {{-- Parent varsa "Parent > Folder" şeklinde göster --}}
                                        {{ $doc->folder->parent ? $doc->folder->parent->name . ' > ' : '' }}{{ $doc->folder->name }}
                                    </a>
                                @else
                                    <span
                                        style="display: inline-flex; align-items: center; gap: 6px; background: #f1f5f9; color: var(--text-muted); padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; border: 1px solid #e2e8f0;">
                                        <i data-lucide="globe" style="width: 14px; height: 14px;"></i>
                                        {{ __('Ana Dizin (Global)') }}
                                    </span>
                                @endif
                            </div>
                        </td>

                        {{-- 4. STATÜ --}}
                        <td style="padding: 15px 20px; vertical-align: top;">
                            <span class="badge {{ $doc->status_color }}"
                                style="padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                                {{ mb_strtoupper(__($doc->status_text)) }}
                            </span>
                        </td>

                        {{-- 5. TARİH --}}
                        <td
                            style="padding: 15px 20px; vertical-align: top; font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">
                            <div style="display: flex; align-items: center; gap: 4px;">
                                <i data-lucide="calendar" style="width: 14px;"></i>
                                {{ $doc->created_at->format('d M Y') }}
                            </div>
                        </td>

                        {{-- 6. İŞLEMLER --}}
                        <td style="padding: 15px 20px; vertical-align: top; text-align: right;">
                            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                <a href="{{ route('documents.show', $doc->id) }}"
                                    class="btn btn-sm btn-outline-primary" title="{{ __('Görüntüle') }}"
                                    style="padding: 6px 10px;">
                                    <i data-lucide="eye" style="width: 16px;"></i>
                                </a>

                                @can('delete', $doc)
                                    <form action="{{ route('documents.destroy', $doc->id) }}" method="POST"
                                        onsubmit="return confirm('{{ __('Bu belgeyi silmek istediğinize emin misiniz? Bu işlem geri alınamaz.') }}')"
                                        style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                            title="{{ __('Sil') }}" style="padding: 6px 10px;">
                                            <i data-lucide="trash-2" style="width: 16px;"></i>
                                        </button>
                                    </form>
                                @endcan

                                @php
                                    // Yük getirmemesi için isFav optimizasyonu
                                    $isFav = auth()->user()->favorites->contains($doc->id);
                                @endphp
                                <button type="button" class="btn btn-sm toggle-fav-btn" data-id="{{ $doc->id }}"
                                    style="padding: 6px 10px; border-radius: 6px; background: transparent; border: 1px solid var(--warning-color); cursor: pointer; transition: transform 0.2s;"
                                    title="{{ __('Favorilere Ekle/Çıkar') }}">
                                    <i data-lucide="star" class="fav-icon"
                                        style="width: 16px; color: var(--warning-color); fill: {{ $isFav ? 'var(--warning-color)' : 'none' }};"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-50 text-muted"
                            style="padding: 60px 20px; text-align: center;">
                            <div
                                style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                <div
                                    style="background: #f1f5f9; padding: 20px; border-radius: 50%; margin-bottom: 15px;">
                                    <i data-lucide="search-x" style="width: 48px; height: 48px; color: #94a3b8;"></i>
                                </div>
                                <h3
                                    style="font-size: 1.2rem; color: var(--text-color); font-weight: 700; margin-bottom: 5px;">
                                    {{ __('Sonuç Bulunamadı') }}</h3>
                                <p style="font-size: 0.95rem;">
                                    {{ __('Arama kriterlerinize uygun veya görüntüleme yetkiniz olan belge bulunamadı.') }}
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-20" style="display: flex; justify-content: flex-end;">
    {{ $documents->links() }}
</div>
