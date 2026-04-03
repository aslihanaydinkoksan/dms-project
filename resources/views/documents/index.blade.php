@extends('layouts.app')

@section('content')
    <div class="page-header flex-between mb-20">
        <div>
            <h1 class="page-title">📂 Tüm Belgeler ve Arşiv</h1>
            <p class="text-muted">Sistemdeki yetkili olduğunuz tüm dökümanlarda hızlı arama yapın.</p>
        </div>

        <div class="header-actions" style="display: flex; gap: 15px; align-items: center;">
            <div class="search-box" style="position: relative; width: 350px;">
                <input type="text" id="liveSearchInput" class="form-control"
                    placeholder="Doküman adı, kodu, kişi veya klasör ara..." value="{{ $keyword ?? '' }}"
                    style="padding-left: 35px; border-radius: 20px;">
                <span style="position: absolute; left: 12px; top: 10px; color: #94a3b8;">🔍</span>
                <span id="searchSpinner" style="position: absolute; right: 12px; top: 10px; display: none;">⏳</span>
            </div>

            <a href="{{ route('documents.create') }}" class="btn btn-primary">📤 Yeni Yükle</a>
        </div>
    </div>

    <div class="card glass-card p-20">
        <div id="document-list-container">
            @include('documents.partials.list')
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Konsol kalabalığını temizledik, sadece işini yapan saf JS kaldı
            const searchInput = document.getElementById('liveSearchInput');
            const listContainer = document.getElementById('document-list-container');
            const spinner = document.getElementById('searchSpinner');

            if (!searchInput || !listContainer) return;

            let debounceTimer;

            searchInput.addEventListener('input', function(e) {
                clearTimeout(debounceTimer);
                const query = e.target.value;

                if (spinner) spinner.style.display = 'inline-block';

                debounceTimer = setTimeout(() => {
                    fetchDocuments(query);
                }, 400);
            });

            function fetchDocuments(query) {
                const url = new URL(window.location.href);
                if (query) {
                    url.searchParams.set('q', query);
                } else {
                    url.searchParams.delete('q');
                }

                url.searchParams.delete('page');
                window.history.pushState({}, '', url);

                fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Sunucu Hatası');
                        return response.text();
                    })
                    .then(html => {
                        listContainer.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Arama Hatası:', error);
                    })
                    .finally(() => {
                        if (spinner) spinner.style.display = 'none';
                    });
            }
        });
    </script>
@endpush
