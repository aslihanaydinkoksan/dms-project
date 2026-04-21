<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentPhysicalRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'receiver_ids' => 'nullable|required_if:action,initiate|array',
            'receiver_ids.*' => 'exists:users,id',
            'location_details' => 'nullable|string|max:255',
            'comment' => 'required|string|min:5|max:1000', // Açıklama her işlemde ZORUNLU
            'action' => 'required|in:initiate,accept,reject'
        ];
    }
}
