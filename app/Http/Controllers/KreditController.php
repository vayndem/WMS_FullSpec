<?php

namespace App\Http\Controllers;

use App\Models\Kredit;
use App\Http\Requests\StoreKreditRequest;
use App\Http\Requests\UpdateKreditRequest;

class KreditController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Kredit::class);

        $kredits = Kredit::latest()->get();

        return response()->json($kredits);
    }

    public function store(StoreKreditRequest $request)
    {
        $kredit = Kredit::create($request->validated());

        return response()->json(['message' => 'Data kredit berhasil ditambahkan.', 'data' => $kredit], 201);
    }

    public function show(Kredit $kredit)
    {
        $this->authorize('view', $kredit);

        return response()->json($kredit);
    }

    public function update(UpdateKreditRequest $request, Kredit $kredit)
    {
        $kredit->update($request->validated());

        return response()->json(['message' => 'Data kredit berhasil diperbarui.', 'data' => $kredit]);
    }

    public function destroy(Kredit $kredit)
    {
        $this->authorize('delete', $kredit);

        $kredit->delete();

        return response()->json(['message' => 'Data kredit berhasil dihapus.']);
    }
}
