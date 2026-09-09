<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('user.manage');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'tc_no' => 'nullable|string|max:11|unique:users,tc_no',
            'registration_no' => 'nullable|string|max:50|unique:users,registration_no',
            'password' => 'required|string|min:6',
            'department_id' => 'nullable|exists:departments,id',
            'is_active' => 'boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
        ];
    }
}
