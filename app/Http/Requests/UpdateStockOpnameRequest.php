<?php

namespace App\Http\Requests;

use App\Models\StockOpname;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStockOpnameRequest extends StoreStockOpnameRequest
{
    public function authorize(): bool
    {
        $opname = $this->route('stock_opname');
        return $opname instanceof StockOpname && ($this->user()?->can('update', $opname) ?? false);
    }
}
