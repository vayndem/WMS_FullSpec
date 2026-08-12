<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentOperationGuard
{
    public function claim(string $documentType, int $documentId, string $operation): void
    {
        $key = strtoupper("{$documentType}:{$documentId}:{$operation}");
        $inserted = DB::table('document_operation_keys')->insertOrIgnore([
            'operation_key' => $key,
            'document_type' => strtoupper($documentType),
            'document_id' => $documentId,
            'operation' => strtoupper($operation),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if ($inserted !== 1) throw new RuntimeException('Operasi dokumen sudah pernah diproses; permintaan duplikat ditolak.');
    }
}
