<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KategoriBahan;
use Yajra\DataTables\Facades\DataTables; // Tambahkan ini

class KategoriController extends Controller
{
    public function index()
    {
        $user = session('user_data');

        return view('purchasing.kategori', compact('user'));
    }

    public function fetchKategori()
    {
        $kategori = KategoriBahan::select(['katid', 'katnama']); // Ambil kolom yang diperlukan
        return DataTables::of($kategori)
            ->addIndexColumn() // Tambah index otomatis
            ->addColumn('action', function ($row) {
                return '';
            })
            ->rawColumns(['action']) // Jika ada kolom HTML
            ->make(true);
    }

    // Store a new category
    public function storeKategori(Request $request)
    {
        $request->validate([
            'katnama' => 'required|max:50',
        ]);

        $kategori = KategoriBahan::create([
            'katnama' => strtoupper($request->katnama),
        ]);

        return response()->json(['success' => 'Kategori berhasil ditambahkan!', 'data' => $kategori]);
    }

    // Update a category
    public function updateKategori(Request $request, $id)
    {
        $request->validate([
            'katnama' => 'required|max:50',
        ]);

        $kategori = KategoriBahan::findOrFail($id);
        $kategori->update([
            'katnama' => strtoupper($request->katnama),
        ]);

        return response()->json(['success' => 'Kategori berhasil diubah!', 'data' => $kategori]);
    }

    // Delete a category
    public function deleteKategori($id)
    {
        $kategori = KategoriBahan::findOrFail($id);
        $kategori->delete();

        return response()->json(['success' => 'Kategori berhasil dihapus!']);
    }
}
