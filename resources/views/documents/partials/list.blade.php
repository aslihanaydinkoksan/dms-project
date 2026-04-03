<div class="table-responsive">
    <table class="table modern-table" style="width: 100%; font-size: 0.95rem;">
        <thead>
            <tr>
                <th>Doküman Kodu</th>
                <th>Başlık</th>
                <th>Kategori / Klasör</th>
                <th>Gizlilik</th>
                <th>Statü</th>
                <th class="text-right">İşlemler</th>
            </tr>
        </thead>
        <tbody>
            @forelse($documents as $doc)
                <tr>
                    <td class="font-bold">
                        <a href="{{ route('documents.show', $doc->id) }}"
                            style="color: var(--primary-color); text-decoration: none;">
                            {{ $doc->document_number }}
                        </a>
                    </td>
                    <td>
                        {{ $doc->title }}
                        <div style="font-size: 0.75rem; color: #94a3b8;">👤
                            {{ $doc->currentVersion?->createdBy?->name ?? 'Bilinmiyor' }}</div>
                    </td>
                    <td>
                        {{ $doc->category ?? 'Kategorisiz' }}
                        <div style="font-size: 0.75rem; color: #94a3b8;">📁 {{ $doc->folder?->name ?? 'Ana Dizin' }}
                        </div>
                    </td>
                    <td><span class="badge badge-warning">{{ mb_strtoupper($doc->privacy_level_text) }}</span></td>
                    <td><span class="badge badge-secondary">{{ strtoupper($doc->status_text) }}</span></td>
                    <td class="text-right">
                        <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">

                            <a href="{{ route('documents.show', $doc->id) }}"
                                class="btn btn-sm btn-outline-primary">İncele</a>

                            @can('delete', $doc)
                                <form action="{{ route('documents.destroy', $doc->id) }}" method="POST" style="margin: 0;"
                                    onsubmit="return confirm('DİKKAT: Bu belgeyi sistemden kaldırmak (Soft Delete) istediğinize emin misiniz?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Belgeyi Sil"
                                        style="display: flex; align-items: center; gap: 5px; padding: 4px 10px; background: #fff;">
                                        <i data-lucide="trash-2" style="width: 16px;"></i> Sil
                                    </button>
                                </form>
                            @endcan

                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center p-30 text-muted">
                        <div style="font-size: 2rem; margin-bottom: 10px;">🔍</div>
                        Aradığınız kriterlere uygun belge bulunamadı.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-20 pagination-wrapper">
    {{ $documents->appends(request()->query())->links('pagination::bootstrap-5') }}
</div>
