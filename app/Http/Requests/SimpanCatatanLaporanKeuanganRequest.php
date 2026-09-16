<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SimpanCatatanLaporanKeuanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewFinancialStatements');
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'gambaran_umum' => ['nullable', 'string', 'max:20000'],
            'kebijakan_akuntansi' => ['nullable', 'string', 'max:20000'],
            'peristiwa_setelah_periode' => ['nullable', 'string', 'max:20000'],
            'komitmen_kontinjensi' => ['nullable', 'string', 'max:20000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'from' => 'periode awal',
            'to' => 'periode akhir',
            'gambaran_umum' => 'gambaran umum entitas',
            'kebijakan_akuntansi' => 'ikhtisar kebijakan akuntansi',
            'peristiwa_setelah_periode' => 'peristiwa setelah periode pelaporan',
            'komitmen_kontinjensi' => 'komitmen dan kontinjensi',
        ];
    }
}
