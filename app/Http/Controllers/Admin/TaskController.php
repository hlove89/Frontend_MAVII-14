<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TaskController extends Controller
{
    // Proses simpan task baru
    public function store(Request $request)
    {
        $response = Http::withToken(session('access_token'))
            ->post(env('VITE_API_BASE_URL') . '/api/admin/tasks', $request->all());

        if ($response->successful()) {
            return redirect()->back()->with('success', 'Task berhasil dibuat!');
        }

        return redirect()->back()->withErrors($response->json('message') ?? 'Gagal membuat task');
    }

    // Proses update task
    public function update(Request $request, $id)
    {
        $response = Http::withToken(session('access_token'))
            ->put(env('VITE_API_BASE_URL') . '/api/admin/tasks/' . $id, $request->only('status'));

        if ($response->successful()) {
            return redirect()->back()->with('success', 'Task berhasil diupdate!');
        }

        return redirect()->back()->withErrors($response->json('message') ?? 'Gagal mengupdate task');
    }

    // Proses hapus task
    public function destroy($id)
    {
        $response = Http::withToken(session('access_token'))
            ->delete(env('VITE_API_BASE_URL') . '/api/admin/tasks/' . $id);

        if ($response->successful()) {
            return redirect()->back()->with('success', 'Task berhasil dihapus!');
        }

        return redirect()->back()->withErrors($response->json('message') ?? 'Gagal menghapus task');
    }
}