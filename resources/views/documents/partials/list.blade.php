<div class="card" style="border-radius: 12px; overflow: hidden; border: 1px solid var(--border-color);">
    <table class="table modern-table" style="margin: 0; width: 100%; text-align: left;">
        <thead style="background: #f8fafc; color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase;">
            <tr>
                <th style="padding: 15px;">{{ __('Doküman Bilgisi') }}</th>
                <th style="padding: 15px;">{{ __('Doküman Tipi') }}</th>
                <th style="padding: 15px;">{{ __('Gizlilik ve Konum') }}</th>
                <th style="padding: 15px;">{{ __('Statü') }}</th>
                <th style="padding: 15px;">{{ __('Tarih') }}</th>
                <th class="text-right" style="padding: 15px;">{{ __('İşlemler') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($documents as $doc)
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 15px;">
                        <div
                            style="font-weight: 700; color: var(--primary-color); display: flex; align-items: center; gap: 6px;">
                            {{ $doc->document_number }}
                            {{-- Sadece Kasa Gerektirenlerde İkon Göster --}}
                            @if ($doc->requires_vault)
                                <i data-lucide="shield-check" style="width: 14px; color: var(--warning-color);"
                                    title="{{ __('Kasa Korumalı') }}"></i>
                            @endif
                        </div>
                        <div style="font-size: 0.9rem; font-weight: 500; margin-bottom: 4px;">{{ $doc->title }}</div>
                        <div
                            style="font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px;">
                            <i data-lucide="user" style="width: 12px; height: 12px;"></i>
                            <span>{{ __('Yükleyen:') }} </span>
                            @if ($doc->currentVersion && $doc->currentVersion->createdBy)
                                <a href="{{ route('profile.show', $doc->currentVersion->createdBy->id) }}"
                                    target="_blank" rel="noopener noreferrer"
                                    style="color: var(--accent-color); text-decoration: none; font-weight: 600; transition: opacity 0.2s;"
                                    onmouseover="this.style.textDecoration='underline'"
                                    onmouseout="this.style.textDecoration='none'">
                                    {{ $doc->currentVersion->createdBy->name }}
                                </a>
                            @else
                                <span style="font-style: italic;">{{ __('Bilinmiyor') }}</span>
                            @endif
                        </div>
                    </td>
                    <td style="padding: 15px;">
                        <span class="badge badge-secondary"
                            style="font-size: 0.7rem;">{{ $doc->documentType?->name ? __($doc->documentType->name) : __('Belirtilmemiş') }}</span>
                    </td>

                    <td style="padding: 15px;">
                        <span class="badge {{ $doc->privacy_color }}" style="margin-bottom: 4px;">
                            {{ mb_strtoupper(__($doc->privacy_level_text)) }}
                        </span>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                            📁 {{ $doc->folder?->name ?? __('Ana Dizin') }}
                        </div>
                    </td>

                    <td style="padding: 15px;">
                        <span class="badge {{ $doc->status_color }}">
                            {{ mb_strtoupper(__($doc->status_text)) }}
                        </span>
                    </td>

                    <td style="padding: 15px; font-size: 0.85rem; color: var(--text-muted);">
                        {{ $doc->created_at->format('d.m.Y') }}
                    </td>
                    <td class="text-right" style="padding: 15px;">
                        <div style="display: flex; justify-content: flex-end; gap: 8px;">
                            <a href="{{ route('documents.show', $doc->id) }}" class="btn btn-sm btn-outline-primary"
                                title="{{ __('Görüntüle') }}">
                                <i data-lucide="eye" style="width: 16px;"></i>
                            </a>

                            @can('delete', $doc)
                                <form action="{{ route('documents.destroy', $doc->id) }}" method="POST"
                                    onsubmit="return confirm('{{ __('Bu belgeyi silmek istediğinize emin misiniz? Bu işlem geri alınamaz.') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                        title="{{ __('Sil') }}">
                                        <i data-lucide="trash-2" style="width: 16px;"></i>
                                    </button>
                                </form>
                            @endcan
                            @php
                                // Belgenin kullanıcının favorilerinde olup olmadığını kontrol et
                                $isFav = auth()->user()->favorites->contains($doc->id); // $document veya $doc (döngüdeki değişken adına göre düzelt)
                            @endphp
                            <button type="button" class="btn btn-sm toggle-fav-btn" data-id="{{ $doc->id }}"
                                style="padding: 6px; border-radius: 8px; background: transparent; border: 1px solid var(--warning-color); cursor: pointer; transition: transform 0.2s;"
                                title="{{ __('Favorilere Ekle/Çıkar') }}">
                                <i data-lucide="star" class="fav-icon"
                                    style="width: 16px; color: var(--warning-color); fill: {{ $isFav ? 'var(--warning-color)' : 'none' }};"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-50 text-muted" style="padding: 40px; text-align: center;">
                        <i data-lucide="search-x"
                            style="width: 48px; height: 48px; color: #cbd5e1; margin-bottom: 10px;"></i>
                        <p>{{ __('Arama kriterlerine uygun belge bulunamadı.') }}</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-20">
    {{ $documents->links() }}
</div>
