<?php

namespace App\Http\Controllers;

use App\Services\AntreanKerjaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AntreanKerjaController extends Controller
{
    public function __construct(private AntreanKerjaService $antrean) {}

    public function index(Request $request)
    {
        abort_unless(Gate::allows('viewWmsControl'), 403);

        return view('wms_control.antrean-kerja', $this->antrean->untuk($request->user()));
    }
}
