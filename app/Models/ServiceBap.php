<?php

namespace App\Models;

class ServiceBap extends Lpb
{
    protected static function booted(): void
    {
        static::addGlobalScope('service_bap', fn($query) => $query->where('document_type', 'SERVICE_BAP'));
        static::creating(function ($model) {
            $model->document_type = 'SERVICE_BAP';
            $model->jenis_lpb = 3;
        });
    }
}
