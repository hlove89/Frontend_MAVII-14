<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    // Tampilkan monitoring tracking
    public function index()
    {
        $technicians = User::where('role', 'technician')
            ->where('is_online', true)
            ->get();

        foreach ($technicians as $tech) {
            $activeTask = Task::where('technician_id', $tech->id)
                ->whereIn('status', ['assigned', 'accepted', 'on-going'])
                ->latest()
                ->first();

            if ($activeTask) {
                $tech->current_task = $activeTask->title;
                $tech->task_status = $activeTask->status;
                $tech->task_address = $activeTask->address;
                $tech->tech_latitude = $tech->latitude ?? -6.2088;
                $tech->tech_longitude = $tech->longitude ?? 106.8456;
                $tech->task_latitude = $activeTask->latitude ?? -6.2088;
                $tech->task_longitude = $activeTask->longitude ?? 106.8456;
                $tech->actions = $activeTask->actions;
                $tech->catatan = $activeTask->catatan ?? 'Tidak ada catatan';
            } else {
                $tech->current_task = 'Tidak ada tugas';
                $tech->task_status = 'assigned';
                $tech->task_address = 'Alamat tidak tersedia';
                $tech->tech_latitude = $tech->latitude ?? -6.2088;
                $tech->tech_longitude = $tech->longitude ?? 106.8456;
                $tech->task_latitude = $tech->latitude ?? -6.2088;
                $tech->task_longitude = $tech->longitude ?? 106.8456;
                $tech->actions = [];
                $tech->catatan = 'Tidak ada catatan';
            }
        }

        return view('admin.tracking', compact('technicians'));
    }

    private function getDefaultActions($title)
    {
        $actions = [
            'Perbaikan AC' => [
                'Mengecek unit AC indoor',
                'Mengecek unit AC outdoor',
                'Membersihkan filter AC',
                'Menambah freon',
                'Testing AC menyala normal'
            ],
            'Troubleshoot Jaringan' => [
                'Restart router utama',
                'Konfigurasi ulang IP',
                'Penggantian kabel LAN',
                'Testing koneksi berhasil'
            ],
            'Konfigurasi Router' => [
                'Cek konfigurasi existing',
                'Setting ulang router',
                'Konfigurasi DHCP',
                'Testing koneksi internet'
            ]
        ];

        return $actions[$title] ?? [
            'Mengecek perangkat',
            'Melakukan perbaikan',
            'Testing fungsi',
            'Membersihkan area kerja'
        ];
    }

    // Tampilkan rute map
    public function show($id)
    {
        $task = Task::with('technician')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $task->id,
                'title' => $task->title,
                'technician_name' => $task->technician->name ?? 'Teknisi',
                'latitude' => $task->latitude ?? $task->technician->latitude ?? -6.2088,
                'longitude' => $task->longitude ?? $task->technician->longitude ?? 106.8456,
                'status' => $task->status,
                'actions' => $task->actions ?? [],
                'catatan' => $task->catatan ?? 'Tidak ada catatan',
                'address' => $task->address ?? '',
            ]
        ]);
    }

    // Update lokasi tracking manual
    public function updateLocation(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $task->latitude = $request->latitude;
        $task->longitude = $request->longitude;
        $task->save();

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);
        return response()->json(['success' => true]);
    }
}