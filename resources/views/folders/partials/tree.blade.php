<ul class="tree-list" style="list-style: none; padding-left: 0;">
    @foreach ($folders as $folder)
        <li class="tree-item" style="margin-bottom: 10px;">
            <div class="tree-node flex-between"
                style="padding: 12px 15px; background: #fff; border: 1px solid var(--border-color); border-radius: 8px; transition: all 0.2s ease;">
                <div class="tree-info" style="display: flex; align-items: center; gap: 12px;">
                    <i data-lucide="folder" style="color: var(--accent-color); width: 20px; height: 20px;"></i>
                    <span class="tree-name"
                        style="font-weight: 500; color: var(--text-color); font-size: 0.95rem;">{{ $folder->name }}</span>
                </div>

                <div class="tree-actions" style="display: flex; gap: 8px;">
                    <a href="#" class="btn btn-sm btn-outline-secondary" style="padding: 6px;"
                        title="{{ __('Düzenle') }}">
                        <i data-lucide="edit-2" style="width: 14px; height: 14px;"></i>
                    </a>
                    <form action="#" method="POST" class="inline-form"
                        onsubmit="return confirm('{{ __('Klasörü silmek istediğinize emin misiniz?') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" style="padding: 6px;"
                            title="{{ __('Sil') }}">
                            <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                        </button>
                    </form>
                </div>
            </div>

            @if ($folder->childrenRecursive->isNotEmpty())
                <div class="tree-children"
                    style="padding-left: 25px; margin-top: 10px; border-left: 2px dashed var(--border-color); margin-left: 10px;">
                    @include('folders.partials.tree', ['folders' => $folder->childrenRecursive])
                </div>
            @endif
        </li>
    @endforeach
</ul>
