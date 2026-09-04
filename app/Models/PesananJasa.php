<?php

namespace App\Models;

class PesananJasa extends PesananPembelian
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::addGlobalScope('service', fn($query) => $query->where('document_type', 'SERVICE'));
        static::creating(fn($model) => $model->document_type = 'SERVICE');
    }
}
