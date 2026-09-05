<?php

namespace App\Services;

use App\Models\MaterialRequest;
use App\Models\RequestDetail;

class MaterialRequestFulfillmentService
{
    public function syncRealisasi(RequestDetail $reqDetail): void
    {
        $reqDetail->update(['realisasi' => $reqDetail->pembelianDetails()->sum('jumlah')]);
        $this->syncRequestStatus($reqDetail->request_id);
    }

    private function syncRequestStatus(int $requestId): void
    {
        $request = MaterialRequest::with('details')->find($requestId);
        if (!$request || !in_array($request->status, [MaterialRequest::APPROVED, MaterialRequest::FULFILLED], true)) {
            return;
        }

        $fulfilled = $request->details->isNotEmpty() && $request->details->every(
            fn($detail) => (float) $detail->realisasi >= (float) ($detail->jumlah_acc ?? 0) - 0.000001
        );
        $target = $fulfilled ? MaterialRequest::FULFILLED : MaterialRequest::APPROVED;

        if ($request->status !== $target) {
            $request->update(['status' => $target]);
        }
    }
}
