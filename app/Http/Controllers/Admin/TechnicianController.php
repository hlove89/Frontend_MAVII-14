<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TechnicianController extends Controller
{
    public function store(Request $request)
    {
        $response = Http::withToken(session('access_token'))
            ->post(env('VITE_API_BASE_URL') . '/api/admin/technicians', $request->all());

        if ($response->successful()) {
            return redirect()->back()->with('success', 'Teknisi berhasil dibuat!');
        }

        return redirect()->back()->withErrors($response->json('message') ?? $response->json('errors') ?? 'Gagal membuat teknisi');
    }

    public function update(Request $request, $id)
    {
        $response = Http::withToken(session('access_token'))
            ->put(env('VITE_API_BASE_URL') . '/api/admin/technicians/' . $id, $request->all());

        if ($response->successful()) {
            return redirect()->back()->with('success', 'Teknisi berhasil diupdate!');
        }

        return redirect()->back()->withErrors($response->json('message') ?? 'Gagal mengupdate teknisi');
    }

    public function destroy($id)
    {
        $response = Http::withToken(session('access_token'))
            ->delete(env('VITE_API_BASE_URL') . '/api/admin/technicians/' . $id);

        if ($response->successful()) {
            return redirect()->back()->with('success', 'Teknisi berhasil dihapus!');
        }

        return redirect()->back()->withErrors($response->json('message') ?? 'Gagal menghapus teknisi');
    }
}