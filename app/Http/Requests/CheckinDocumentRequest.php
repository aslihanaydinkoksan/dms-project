<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckinDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Kullanıcı giriş yapmış mı? (Kilitleyen kişi kontrolünü Controller'da yapıyoruz)
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // ZIRHLI DOSYA KONTROLÜ
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png,html',
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png,text/html',
                'max:20480' // Maksimum 20 MB
            ],
            // Yeni versiyon yüklerken girilen açıklama notu (Opsiyonel)
            'revision_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Lütfen yeni versiyon için bir dosya seçin.',
            'file.mimes' => 'Sisteme sadece PDF, Word, JPG, PNG ve HTML formatında belgeler yüklenebilir.',
            'file.mimetypes' => 'Dosya uzantısı değiştirilmiş zararlı bir içerik tespit edildi. İşlem reddedildi.',
            'file.max' => 'Yükleyeceğiniz dosya boyutu 20 MB sınırını aşamaz.',
        ];
    }
}
