<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class ApiUser extends Authenticatable
{
    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'type' => 'integer',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->fill($attributes);
    }
}
