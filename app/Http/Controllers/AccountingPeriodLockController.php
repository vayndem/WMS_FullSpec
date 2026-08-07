<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountingPeriodLockRequest;
use App\Http\Requests\UnlockAccountingPeriodRequest;
use App\Models\AccountingPeriodLock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountingPeriodLockController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', AccountingPeriodLock::class);

        if ($request->ajax()) {
            return datatables()->of(AccountingPeriodLock::query()->latest('period_end'))
                ->addColumn('can_unlock', fn ($row) => $request->user()->can('unlock', $row))
                ->make(true);
        }

        return view('accounting_period_lock.index');
    }

    public function store(StoreAccountingPeriodLockRequest $request)
    {
        $data = $request->validated();
        $overlap = AccountingPeriodLock::query()->where('status', 'LOCKED')
            ->whereDate('period_start', '<=', $data['period_end'])
            ->whereDate('period_end', '>=', $data['period_start'])
            ->exists();
        abort_if($overlap, 422, 'Rentang tersebut bertabrakan dengan periode lain yang masih dikunci.');

        $user = Auth::user();
        $lock = AccountingPeriodLock::create([
            ...$data,
            'status' => 'LOCKED',
            'locked_by' => Auth::id(),
            'locked_by_name' => $user->name ?? $user->nama ?? null,
            'locked_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Periode berhasil dikunci.', 'data' => $lock], 201);
    }

    public function unlock(UnlockAccountingPeriodRequest $request, AccountingPeriodLock $periodLock)
    {
        DB::transaction(function () use ($request, $periodLock) {
            $locked = AccountingPeriodLock::lockForUpdate()->findOrFail($periodLock->id);
            abort_unless($locked->isLocked(), 422, 'Periode ini sudah dibuka.');
            $user = Auth::user();
            $locked->update([
                'status' => 'UNLOCKED',
                'unlocked_by' => Auth::id(),
                'unlocked_by_name' => $user->name ?? $user->nama ?? null,
                'unlocked_at' => now(),
                'unlock_reason' => $request->validated('unlock_reason'),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Periode berhasil dibuka kembali.']);
    }
}
