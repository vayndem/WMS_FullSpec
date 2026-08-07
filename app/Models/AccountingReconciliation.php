<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only projection used by the reconciliation screen.
 */
class AccountingReconciliation extends Model
{
    protected $table = 'bahan';

    public $timestamps = false;

    protected $guarded = [];
}
