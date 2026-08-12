<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only projection used by the reconciliation screen.
 */
class AccountingReconciliation extends Model
{
    protected $table = 'bahans';

    public $timestamps = false;

    protected $guarded = [];
}
