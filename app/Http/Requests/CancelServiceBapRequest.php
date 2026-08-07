<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelServiceBapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cancel', $this->route('service_bap'));
    }
    public function rules(): array
    {
        return ['reason' => 'required|string|max:1000'];
    }
}
