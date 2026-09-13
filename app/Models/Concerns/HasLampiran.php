<?php

namespace App\Models\Concerns;

use App\Models\LampiranDokumen;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasLampiran
{
    public function lampiran(): MorphMany
    {
        return $this->morphMany(LampiranDokumen::class, 'lampiran')->latest('id');
    }
}
