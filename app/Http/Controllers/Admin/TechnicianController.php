<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TechnicianController extends Controller
{
    // Proses simpan teknisi
    public function store(Request $request)
    {
        $response = Http::withToken(session('access_token'))
            ->acceptJson()
            ->post(config('app.api_url') . '/api/admin/technicians', $request->all());

        if ($response->successful()) {
            return redirect()->back()->with('success', 'Teknisi berhasil dibuat!');
        }

        $errorData = $response->json();
        $message = $errorData['message'] ?? 'Gagal membuat teknisi';
        
        if (isset($errorData['errors'])) {
            return redirect()->back()->withErrors($errorData['errors'])->withInput();
        }

        return redirect()->back()->with('error', $message)->withInput();
    }

    // Proses update teknisi
    public function update(Request $request, $id)
    {
        $response = Http::withToken(session('access_token'))
            ->acceptJson()
            ->put(config('app.api_url') . '/api/admin/technicians/' . $id, $request->all());

        if ($response->successful()) {
            $message = $response->json('message') ?? 'Teknisi berhasil diupdate!';
            return redirect()->back()->with('success', $message);
        }

        $errorData = $response->json();
        $message = $errorData['message'] ?? 'Gagal mengupdate teknisi';

        if (isset($errorData['errors'])) {
            return redirect()->back()->withErrors($errorData['errors'])->withInput();
        }

        return redirect()->back()->with('error', $message)->withInput();
    }

    // Proses hapus teknisi
    public function destroy(Request $request, $id)
    {
        $response = Http::withToken(session('access_token'))
            ->acceptJson()
            ->delete(config('app.api_url') . '/api/admin/technicians/' . $id);

        if ($response->successful()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Teknisi berhasil dihapus!']);
            }
            return redirect()->back()->with('success', 'Teknisi berhasil dihapus!');
        }

        $message = $response->json('message') ?? 'Gagal menghapus teknisi';
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], $response->status());
        }
        return redirect()->back()->withErrors($message);
    }
}