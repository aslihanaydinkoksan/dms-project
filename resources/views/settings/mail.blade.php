@extends('layouts.app')

@section('content')
    <div class="page-header"
        style="background: var(--surface-color); padding: 25px; border-radius: var(--border-radius); border: 1px solid var(--border-color); box-shadow: var(--card-shadow); margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="background: #eef2ff; color: var(--accent-color); padding: 15px; border-radius: 12px;">
                <i data-lucide="mail-open" style="width: 32px; height: 32px;"></i>
            </div>
            <div>
                <h1 class="page-title" style="margin-bottom: 5px; font-size: 1.5rem; color: var(--primary-color);">
                    {{ __('Mail Şablonları Yönetimi') }}</h1>
                <p class="text-muted" style="margin: 0;">
                    {{ __('Sistemin otomatik göndereceği e-postaların içeriklerini yönetin.') }}</p>
            </div>
        </div>
    </div>

    @include('partials.alerts')

    <form action="{{ route('settings.mail.update') }}" method="POST" class="modern-form">
        @csrf
        @method('PUT')

        @php
            // Veritabanından geldiğini varsaydığımız şablon verileri ve Akıllı Etiket eşleştirmeleri
            $mailTemplates = [
                [
                    'key' => 'physical_receipt',
                    'title' => __('Fiziksel Evrak Teslimat (Zimmet) Bildirimi'),
                    'icon' => 'inbox',
                    'color' => '#d97706',
                    'bg' => '#fffbeb',
                    'subject_val' => __('Fiziksel Evrak Teslimat Bildirimi - {document_code}'),
                    'body_val' => __(
                        "Merhaba {user_name},\n\nSistemimize eklenen {document_code} kayıt numaralı '{document_name}' isimli evrakın ıslak imzalı fiziksel kopyası size zimmetlenmiştir.\n\nLütfen evrakı fiziki olarak teslim aldığınızda aşağıdaki butona tıklayarak sistem üzerinden onaylayınız:\n\n{action_url}\n\nİyi çalışmalar dileriz.",
                    ),
                    'smart_tags' => [
                        '{user_name}' => ['label' => __('Personel Adı'), 'icon' => 'user', 'preview' => 'Ahmet Yılmaz'],
                        '{document_code}' => [
                            'label' => __('Evrak Kodu'),
                            'icon' => 'hash',
                            'preview' => 'SÖZ-2026-001',
                        ],
                        '{document_name}' => [
                            'label' => __('Belge Adı'),
                            'icon' => 'file-text',
                            'preview' => __('Araç Kiralama Sözleşmesi'),
                        ],
                        '{action_url}' => [
                            'label' => __('İşlem Linki'),
                            'icon' => 'link',
                            'preview' =>
                                '<a href="#" style="color: #2563eb; text-decoration: underline;">' .
                                __('Evrakı Teslim Aldığımı Onaylıyorum') .
                                '</a>',
                        ],
                    ],
                ],
                [
                    'key' => 'workflow_pending',
                    'title' => __('İş Akışı: Onay Bekleyen Belge'),
                    'icon' => 'zap',
                    'color' => '#0f766e',
                    'bg' => '#f0fdfa',
                    'subject_val' => __('Onayınız Bekleniyor: {document_name} ({document_code})'),
                    'body_val' => __(
                        "Sayın {user_name},\n\nYeni bir belge onay akışına girmiştir ve {step_order}. Adım onaycısı olarak incelemeniz beklenmektedir.\n\nBelge Detayları:\nBelge: {document_name}\nKodu: {document_code}\n\nBelgeyi incelemek ve kararınızı (Onay/Red) iletmek için lütfen aşağıdaki adrese gidin:\n{action_url}\n\nTeşekkürler.",
                    ),
                    'smart_tags' => [
                        '{user_name}' => ['label' => __('Personel Adı'), 'icon' => 'user', 'preview' => 'Ayşe Demir'],
                        '{document_code}' => ['label' => __('Evrak Kodu'), 'icon' => 'hash', 'preview' => 'TAL-045'],
                        '{document_name}' => [
                            'label' => __('Belge Adı'),
                            'icon' => 'file-text',
                            'preview' => __('Yıllık İzin Talimatnamesi'),
                        ],
                        '{step_order}' => ['label' => __('Adım Sırası'), 'icon' => 'list-ordered', 'preview' => '2'],
                        '{action_url}' => [
                            'label' => __('İşlem Linki'),
                            'icon' => 'link',
                            'preview' =>
                                '<a href="#" style="color: #2563eb; text-decoration: underline;">' .
                                __('Belgeyi İncele ve İşlem Yap') .
                                '</a>',
                        ],
                    ],
                ],
                // KLASÖR YETKİLENDİRME BİLDİRİMİ BURAYA EKLENDİ
                [
                    'key' => 'folder_permission',
                    'title' => __('Klasör Yetkilendirme Bildirimi'),
                    'icon' => 'folder-lock',
                    'color' => '#4338ca',
                    'bg' => '#e0e7ff',
                    'subject_val' =>
                        $settings['mail_subject_folder_permission'] ?? __('Yeni Klasör Yetkisi: {folder_name}'),
                    'body_val' =>
                        $settings['mail_template_folder_permission'] ??
                        __(
                            "Sayın {user_name},\n\n{assigner_name} tarafından size '{folder_name}' klasörü için '{permission_level}' yetkisi tanımlanmıştır.\n\nİyi çalışmalar dileriz.",
                        ),
                    'smart_tags' => [
                        '{user_name}' => ['label' => __('Personel Adı'), 'icon' => 'user', 'preview' => 'Ahmet Yılmaz'],
                        '{assigner_name}' => [
                            'label' => __('Atayan Kişi'),
                            'icon' => 'user-check',
                            'preview' => 'Yönetici Adı',
                        ],
                        '{folder_name}' => [
                            'label' => __('Klasör Adı'),
                            'icon' => 'folder',
                            'preview' => 'Finans Raporları',
                        ],
                        '{permission_level}' => [
                            'label' => __('Yetki Seviyesi'),
                            'icon' => 'shield',
                            'preview' => 'Yükleme ve Görüntüleme',
                        ],
                    ],
                ],
            ];
        @endphp

        <div style="display: flex; flex-direction: column; gap: 40px;">
            @foreach ($mailTemplates as $template)
                <div class="template-section"
                    style="background: var(--surface-color); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: var(--card-shadow); overflow: hidden;">

                    <div
                        style="padding: 20px 25px; border-bottom: 1px solid var(--border-color); background: #f8fafc; display: flex; align-items: center; gap: 12px;">
                        <div
                            style="background: {{ $template['bg'] }}; color: {{ $template['color'] }}; padding: 10px; border-radius: 10px;">
                            <i data-lucide="{{ $template['icon'] }}" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 1.2rem; color: var(--primary-color);">{{ $template['title'] }}
                            </h3>
                            <span style="font-size: 0.85rem; color: var(--text-muted);">{{ __('Sistem Kodu:') }}
                                {{ $template['key'] }}</span>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; min-height: 400px;">

                        <div style="padding: 25px; border-right: 1px solid var(--border-color);">

                            <div style="margin-bottom: 20px;">
                                <label
                                    style="display: block; font-weight: 600; margin-bottom: 10px; color: var(--secondary-color); font-size: 0.9rem;">
                                    ✨ {{ __('Metne Eklenebilecek Akıllı Alanlar:') }}
                                </label>
                                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                    @foreach ($template['smart_tags'] as $tagKey => $tagData)
                                        <button type="button" class="smart-tag-btn"
                                            onclick="insertSmartTag('{{ $template['key'] }}', '{{ $tagKey }}')"
                                            title="{{ $tagKey }} {{ __('kodunu ekler') }}"
                                            style="background: #fff; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 500; color: var(--text-color); cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease;">
                                            <i data-lucide="{{ $tagData['icon'] }}"
                                                style="width: 14px; color: var(--accent-color);"></i>
                                            {{ $tagData['label'] }}
                                        </button>
                                    @endforeach
                                </div>
                                <small
                                    style="display: block; margin-top: 8px; color: var(--text-muted); font-size: 0.8rem;">{{ __('İmleci metin kutusunda istediğiniz yere bırakın ve yukarıdaki etiketlere tıklayın.') }}</small>
                            </div>

                            <div class="form-group" style="margin-bottom: 20px;">
                                <label
                                    style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--secondary-color);">{{ __('Mail Konusu (Subject)') }}</label>
                                <input type="text" id="subject_{{ $template['key'] }}"
                                    name="templates[{{ $template['key'] }}][subject]" class="form-control editor-input"
                                    value="{{ $template['subject_val'] }}" data-template="{{ $template['key'] }}"
                                    style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px;">
                            </div>

                            <div class="form-group" style="margin-bottom: 0;">
                                <label
                                    style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--secondary-color);">{{ __('Mail İçeriği (Body)') }}</label>
                                <textarea id="body_{{ $template['key'] }}" name="templates[{{ $template['key'] }}][body]"
                                    class="form-control editor-input" rows="8" data-template="{{ $template['key'] }}"
                                    style="width: 100%; padding: 15px; border: 1px solid var(--border-color); border-radius: 6px; resize: vertical; line-height: 1.6;">{{ $template['body_val'] }}</textarea>
                            </div>
                        </div>

                        <div style="padding: 25px; background: #f1f5f9; display: flex; flex-direction: column;">
                            <label
                                style="display: flex; align-items: center; gap: 8px; font-weight: 600; margin-bottom: 15px; color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                <i data-lucide="monitor-smartphone" style="width: 16px;"></i>
                                {{ __('Kullanıcıya Gidecek Olan Mail') }}
                            </label>

                            <div
                                style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); flex: 1; display: flex; flex-direction: column; overflow: hidden;">

                                <div style="padding: 15px 20px; border-bottom: 1px solid #f1f5f9; background: #fff;">
                                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 5px;">
                                        {{ __('Kimden:') }}
                                        <strong style="color: var(--text-color);">{{ __('DMS Sistem Bildirimleri') }}
                                            &lt;noreply@sirket.com&gt;</strong>
                                    </div>
                                    <div style="font-size: 1.1rem; font-weight: 600; color: var(--primary-color);"
                                        id="preview_subject_{{ $template['key'] }}">
                                    </div>
                                </div>

                                <div style="padding: 25px 20px; font-size: 0.95rem; color: #334155; line-height: 1.6; flex: 1; overflow-y: auto;"
                                    id="preview_body_{{ $template['key'] }}">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            @endforeach
        </div>

        <div
            style="position: sticky; bottom: 20px; margin-top: 40px; background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 10px 25px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; z-index: 100;">
            <div class="alert alert-info"
                style="margin: 0; padding: 10px 15px; font-size: 0.85rem; background: transparent; border: none; color: var(--text-muted);">
                <i data-lucide="info" style="width: 16px; display: inline-block; vertical-align: middle;"></i>
                {{ __('Değişikliklerinizin aktif olması için kaydetmeyi unutmayın.') }}
            </div>
            <button type="submit" class="btn btn-primary"
                style="padding: 12px 30px; font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                <i data-lucide="save"></i> {{ __('Tüm Şablonları Kaydet') }}
            </button>
        </div>

    </form>
@endsection

@push('styles')
    <style>
        .smart-tag-btn:hover {
            background: #f0f9ff !important;
            border-color: var(--accent-color) !important;
            color: var(--accent-color) !important;
        }

        .preview-highlight {
            background: #fef9c3;
            color: #ca8a04;
            padding: 2px 4px;
            border-radius: 4px;
            font-weight: 600;
        }
    </style>
@endpush

@push('scripts')
    <script>
        const templateData = @json(collect($mailTemplates)->keyBy('key'));

        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();

            document.querySelectorAll('.editor-input').forEach(input => {
                input.addEventListener('input', function() {
                    const templateKey = this.getAttribute('data-template');
                    updateLivePreview(templateKey);
                });
            });

            Object.keys(templateData).forEach(key => updateLivePreview(key));
        });

        window.insertSmartTag = function(templateKey, tagString) {
            const textarea = document.getElementById('body_' + templateKey);

            if (textarea) {
                const startPos = textarea.selectionStart;
                const endPos = textarea.selectionEnd;
                const text = textarea.value;

                textarea.value = text.substring(0, startPos) + tagString + text.substring(endPos, text.length);

                textarea.focus();
                textarea.selectionStart = startPos + tagString.length;
                textarea.selectionEnd = startPos + tagString.length;

                updateLivePreview(templateKey);
            }
        };

        function updateLivePreview(templateKey) {
            const subjectInput = document.getElementById('subject_' + templateKey);
            const bodyInput = document.getElementById('body_' + templateKey);
            const previewSubject = document.getElementById('preview_subject_' + templateKey);
            const previewBody = document.getElementById('preview_body_' + templateKey);

            if (!subjectInput || !bodyInput || !previewSubject || !previewBody) return;

            let subjText = subjectInput.value;
            let bodyText = bodyInput.value;

            const smartTags = templateData[templateKey].smart_tags;

            for (const [tag, data] of Object.entries(smartTags)) {
                const regex = new RegExp(tag.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1"), 'g');
                const highlightedPreview = `<span class="preview-highlight">${data.preview}</span>`;

                subjText = subjText.replace(regex, highlightedPreview);
                bodyText = bodyText.replace(regex, highlightedPreview);
            }

            bodyText = bodyText.replace(/\n/g, '<br>');

            previewSubject.innerHTML = subjText;
            previewBody.innerHTML = bodyText;
        }
    </script>
@endpush
