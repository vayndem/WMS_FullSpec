<?php
namespace App\Http\Controllers;
use App\Http\Requests\StoreAssetCategoryRequest;
use App\Models\AssetCategory;
use App\Models\ChartOfAccount;
class AssetCategoryController extends Controller {
    public function index() {
        $this->authorize('viewAny',AssetCategory::class);
        $categories=AssetCategory::with(['assetAccount','accumulatedAccount','expenseAccount'])->orderBy('code')->get();
        $accounts=ChartOfAccount::where('is_active',true)->where('is_postable',true)->orderBy('kode_akun')->get();
        return view('asset_categories.index',compact('categories','accounts'));
    }
    public function store(StoreAssetCategoryRequest $request) {
        AssetCategory::create($request->validated()+['is_active'=>$request->boolean('is_active',true)]);
        return back()->with('success','Kategori asset berhasil dibuat.');
    }
    public function update(StoreAssetCategoryRequest $request, AssetCategory $assetCategory) {
        $assetCategory->update($request->validated()+['is_active'=>$request->boolean('is_active')]);
        return back()->with('success','Kategori asset berhasil diperbarui.');
    }
    public function destroy(AssetCategory $assetCategory) {
        $this->authorize('delete',$assetCategory); $assetCategory->delete();
        return back()->with('success','Kategori asset berhasil dihapus.');
    }
}
