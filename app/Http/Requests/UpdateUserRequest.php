<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($this->route('user')->id)],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'type' => 'required|integer|exists:user_roles,id',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'type' => 'peran',
        ];
    }
}
