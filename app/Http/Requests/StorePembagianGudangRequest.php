<?php
namespace App\Http\Requests;
use App\Models\PembagianGudang;
use Illuminate\Foundation\Http\FormRequest;
class StorePembagianGudangRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('create',PembagianGudang::class) ?? false; }
    public function rules(): array { return ['user_id'=>'required|exists:users,id','gudang_id'=>'required|exists:gudangs,id','boleh_menerima'=>'nullable|boolean','boleh_npk'=>'nullable|boolean','boleh_transfer'=>'nullable|boolean','boleh_opname'=>'nullable|boolean']; }
}
