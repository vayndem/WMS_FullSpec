<?php

namespace App\Http\Controllers;

use App\Models\StokGudang;
use App\Services\RekonsiliasiGudangService;

class RekonsiliasiGudangController extends Controller
{
    public function __construct(private RekonsiliasiGudangService $service) {}
    public function index()
    {
        $this->authorize('viewAny', StokGudang::class);
        return view('rekonsiliasi_gudangs.index', ['rows' => $this->service->rows()]);
    }
}
