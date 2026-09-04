<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogAudit extends Model
{
    protected $table = 'wms_log_audit';
    protected $guarded = ['id'];
    protected $casts = ['old_values' => 'array', 'new_values' => 'array', 'metadata' => 'array'];
}
