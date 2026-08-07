<?php

namespace App\Http\Controllers;

use App\Models\Debit;
use App\Http\Requests\StoreDebitRequest;
use App\Http\Requests\UpdateDebitRequest;

class DebitController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Debit::class);

        $debits = Debit::latest()->get();

        return response()->json($debits);
    }

    public function store(StoreDebitRequest $request)
    {
        $debit = Debit::create($request->validated());

        return response()->json(['message' => 'Data debit berhasil ditambahkan.', 'data' => $debit], 201);
    }

    public function show(Debit $debit)
    {
        $this->authorize('view', $debit);

        return response()->json($debit);
    }

    public function update(UpdateDebitRequest $request, Debit $debit)
    {
        $debit->update($request->validated());

        return response()->json(['message' => 'Data debit berhasil diperbarui.', 'data' => $debit]);
    }

    public function destroy(Debit $debit)
    {
        $this->authorize('delete', $debit);

        $debit->delete();

        return response()->json(['message' => 'Data debit berhasil dihapus.']);
    }
}
