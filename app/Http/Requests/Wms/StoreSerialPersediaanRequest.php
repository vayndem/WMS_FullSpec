<?php

namespace App\Http\Requests\Wms;

use App\Models\LotPersediaan;
use Illuminate\Validation\Validator;

class StoreSerialPersediaanRequest extends WarehouseOperationRequest
{
    public function rules(): array
    {
        return [
            'inventory_lot_id' => ['required', 'integer', 'exists:wms_lot_persediaan,id'],
            'serial_number' => ['required', 'string', 'max:120', 'unique:wms_serial_persediaan,serial_number'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $lot = LotPersediaan::find($this->input('inventory_lot_id'));
            if (!$lot) {
                return;
            }

            $diterima = (float) $lot->layers()->sum('initial_quantity');
            $terdaftar = $lot->serials()->count();
            if ($diterima > 0 && $terdaftar + 1 > $diterima) {
                $validator->errors()->add('serial_number', "Lot {$lot->lot_number} hanya menerima " . rtrim(rtrim(number_format($diterima, 4, ',', '.'), '0'), ',') . " unit dan sudah punya {$terdaftar} serial.");
            }
        }];
    }
}
