<?php

namespace App\Services;

use App\Models\AccountingPeriodLock;
use Carbon\Carbon;
use RuntimeException;

class AccountingPeriodService
{
    public function assertOpen($date, string $transactionLabel): void
    {
        $date = Carbon::parse($date)->toDateString();
        $lock = AccountingPeriodLock::query()
            ->where('status', 'LOCKED')
            ->whereDate('period_start', '<=', $date)
            ->whereDate('period_end', '>=', $date)
            ->first();

        if ($lock) {
            throw new RuntimeException(
                "{$transactionLabel} tanggal {$date} ditolak karena periode "
                    . $lock->period_start->format('d-m-Y') . ' s.d. '
                    . $lock->period_end->format('d-m-Y') . ' sudah dikunci.'
            );
        }
    }
}
