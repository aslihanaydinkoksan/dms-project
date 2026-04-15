<ul style="list-style: none; padding: 0; margin: 0;">
    @forelse($favoriteDocuments as $doc)
        <li style="display: flex; justify-content: space-between; align-items: flex-start; padding: 15px 20px; border-bottom: 1px solid var(--border-color); transition: background 0.2s;"
            class="hover-row">

            <div style="display: flex; flex-direction: column; flex-grow: 1; padding-right: 15px;">
                <a href="{{ route('documents.show', $doc->id) }}"
                    style="text-decoration: none; color: var(--primary-color); font-weight: 600; font-size: 1.05rem;">
                    {{ $doc->title }}
                </a>
                <span style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 6px;">
                    {{ $doc->document_number }} | {{ $doc->documentType->name ?? __('Belirtilmemiş') }}
                </span>

                {{-- YENİ: KİŞİSEL NOT BÖLÜMÜ --}}
                <div class="fav-note-wrapper" data-id="{{ $doc->id }}">
                    {{-- Not varsa bunu göster --}}
                    <div class="note-display-box"
                        style="font-size: 0.8rem; color: #475569; background: #f8fafc; padding: 6px 10px; border-radius: 6px; border-left: 3px solid var(--warning-color); display: {{ $doc->pivot->note ? 'flex' : 'none' }}; align-items: center; justify-content: space-between; cursor: pointer; transition: background 0.2s;"
                        title="{{ __('Düzenlemek için tıklayın') }}">
                        <span class="note-text" style="font-style: italic;">{{ $doc->pivot->note }}</span>
                        <i data-lucide="edit-3" style="width: 14px; opacity: 0.4;"></i>
                    </div>

                    {{-- Not yoksa Ekle Butonunu göster --}}
                    <div class="note-add-btn"
                        style="font-size: 0.75rem; color: var(--primary-color); cursor: pointer; display: {{ $doc->pivot->note ? 'none' : 'inline-flex' }}; align-items: center; gap: 4px; font-weight: 600;">
                        <i data-lucide="plus-circle" style="width: 14px;"></i> {{ __('Not Ekle') }}
                    </div>

                    {{-- Düzenleme / Ekleme Input Alanı (Varsayılan olarak gizli) --}}
                    <div class="note-input-box" style="display: none; position: relative;">
                        <input type="text" class="fav-note-input" value="{{ $doc->pivot->note }}"
                            placeholder="{{ __('Kişisel notunuz... (Enter ile kaydet)') }}"
                            style="width: 100%; font-size: 0.8rem; padding: 6px 30px 6px 10px; border-radius: 6px; border: 1px solid var(--primary-color); outline: none; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);">
                        <i data-lucide="corner-down-left"
                            style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 14px; color: var(--primary-color); pointer-events: none;"></i>
                    </div>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-shrink: 0; margin-top: 2px;">
                @can('update', $doc)
                    <a href="{{ route('documents.edit', $doc->id) }}" class="btn btn-sm btn-outline-primary"
                        title="{{ __('Düzenle') }}" style="padding: 6px; border-radius: 8px;">
                        <i data-lucide="edit-3" style="width: 16px;"></i>
                    </a>
                @endcan

                <button type="button" class="btn btn-sm toggle-fav-btn" data-id="{{ $doc->id }}"
                    style="padding: 6px; border-radius: 8px; background: transparent; border: 1px solid var(--warning-color); cursor: pointer;"
                    title="{{ __('Favoriden Çıkar') }}">
                    <i data-lucide="star" class="fav-icon"
                        style="width: 16px; color: var(--warning-color); fill: var(--warning-color);"></i>
                </button>
            </div>
        </li>
    @empty
        <li style="padding: 30px; text-align: center; color: var(--text-muted);">
            <i data-lucide="star-off" style="width: 48px; height: 48px; opacity: 0.3; margin-bottom: 10px;"></i>
            <p style="margin: 0;">
                {{ empty($keyword) ? __('Henüz favori belgeniz bulunmuyor.') : __('Aradığınız kriterde favori belge bulunamadı.') }}
            </p>
        </li>
    @endforelse
</ul>
