<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class TaxRate extends Model
{
    protected $fillable = ['tax_type', 'rate', 'effective_from', 'effective_until', 'is_active', 'description'];
    protected $casts = ['rate' => 'decimal:4', 'effective_from' => 'date', 'effective_until' => 'date', 'is_active' => 'boolean'];

    public static function rateFor(string $type, $date): float
    {
        $rate = static::query()->where('tax_type', $type)->where('is_active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(fn($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date))
            ->latest('effective_from')->value('rate');

        if ($rate === null) {
            throw new RuntimeException("Tarif {$type} untuk tanggal transaksi belum dikonfigurasi.");
        }
        return (float) $rate;
    }
}
