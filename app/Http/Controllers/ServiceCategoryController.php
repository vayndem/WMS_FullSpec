<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateServiceCategoryRequest;
use App\Models\ChartOfAccount;
use App\Models\ServiceCategory;

class ServiceCategoryController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', ServiceCategory::class);
        $categories = ServiceCategory::with(['expenseAccount', 'grniAccount'])->orderBy('display_code')->get();
        $accounts = ChartOfAccount::where('is_active', true)->where('is_postable', true)->orderBy('kode_akun')->get();
        return view('service_categories.index', compact('categories', 'accounts'));
    }
    public function update(UpdateServiceCategoryRequest $request, ServiceCategory $serviceCategory)
    {
        $serviceCategory->update($request->validated() + ['is_active' => $request->boolean('is_active')]);
        return back()->with('success', 'Mapping kategori jasa diperbarui.');
    }
}
