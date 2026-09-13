<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    private const BATAS_LONCENG = 8;

    public function lonceng(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'belum_dibaca' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->limit(self::BATAS_LONCENG)->get()
                ->map(fn ($row) => $this->bentuk($row))->all(),
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        return view('notifikasi.index', [
            'notifikasi' => $user->notifications()->paginate(20),
            'belumDibaca' => $user->unreadNotifications()->count(),
        ]);
    }

    public function baca(Request $request, string $notifikasi)
    {
        $row = $request->user()->notifications()->findOrFail($notifikasi);
        $row->markAsRead();

        $tujuan = $row->data['url'] ?? route('notifikasi.index');

        return $request->expectsJson()
            ? response()->json(['url' => $tujuan])
            : redirect()->to($tujuan);
    }

    public function bacaSemua(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return $request->expectsJson()
            ? response()->json(['status' => 'OK'])
            : back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    private function bentuk($row): array
    {
        return [
            'id' => $row->id,
            'judul' => $row->data['judul'] ?? 'Notifikasi',
            'ringkasan' => $row->data['ringkasan'] ?? '',
            'konteks' => $row->data['konteks'] ?? '',
            'ikon' => $row->data['ikon'] ?? 'fa-bell',
            'warna' => $row->data['warna'] ?? 'info',
            'url' => $row->data['url'] ?? route('notifikasi.index'),
            'dibaca' => $row->read_at !== null,
            'waktu' => $row->created_at->diffForHumans(),
        ];
    }
}
