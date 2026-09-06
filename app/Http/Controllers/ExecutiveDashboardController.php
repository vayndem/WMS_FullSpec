<?php

namespace App\Http\Controllers;

use App\Services\ExecutiveDashboardService;

class ExecutiveDashboardController extends Controller
{
    public function __construct(private ExecutiveDashboardService $dashboard) {}

    public function index()
    {
        $this->authorize('viewExecutiveDashboard');

        $inventoryTrend = $this->dashboard->inventoryValueTrend(6);
        $apAging = $this->dashboard->apAgingBuckets();
        $topSuppliers = $this->dashboard->topSuppliers(5);
        $costByCategory = $this->dashboard->costByCategoryThisYear();

        return view('executive_dashboard.index', compact('inventoryTrend', 'apAging', 'topSuppliers', 'costByCategory'));
    }
}
