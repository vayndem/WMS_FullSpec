<?php
namespace App\Http\Requests;
use App\Models\PemeriksaanConsider;
use Illuminate\Foundation\Http\FormRequest;
class StorePemeriksaanConsiderRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('create',PemeriksaanConsider::class) ?? false; }
    public function rules(): array { return ['nomor_pemeriksaan'=>'required|string|max:50|unique:pemeriksaan_considers,nomor_pemeriksaan','tanggal'=>'required|date','gudang_consider_id'=>'required|exists:gudangs,id','gudang_baik_id'=>'required|different:gudang_consider_id|exists:gudangs,id','gudang_rusak_id'=>'required|different:gudang_consider_id|different:gudang_baik_id|exists:gudangs,id','catatan'=>'nullable|string|max:2000','details'=>'required|array|min:1','details.*.bahan_id'=>'required|distinct|exists:bahan,id','details.*.jumlah_diperiksa'=>'required|numeric|gt:0','details.*.jumlah_baik'=>'required|numeric|min:0','details.*.jumlah_rusak'=>'required|numeric|min:0','details.*.alasan'=>'nullable|string|max:1000']; }
    public function withValidator($validator): void { $validator->after(function($v){ foreach($this->input('details',[]) as $i=>$d){ if(abs(((float)($d['jumlah_baik']??0)+(float)($d['jumlah_rusak']??0))-(float)($d['jumlah_diperiksa']??0))>0.000001) $v->errors()->add("details.$i.jumlah_baik",'Jumlah baik dan rusak harus sama dengan jumlah diperiksa.'); }}); }
}
