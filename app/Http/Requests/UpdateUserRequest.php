<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('user.manage');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            // Mevcut kullanıcının e-postasını unique kontrolünden muaf tutuyoruz
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->route('user'))],
            'password' => 'nullable|string|min:6', // Şifre boş bırakılırsa güncellenmez
            'department_id' => 'nullable|exists:departments,id',
            'is_active' => 'boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
            'can_manage_acl' => 'nullable|boolean',
        ];
    }
}
