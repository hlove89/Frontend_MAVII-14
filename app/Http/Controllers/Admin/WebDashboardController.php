<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WebDashboardController extends Controller
{
    // Tampilkan halaman utama
    public function mainPage()
    {
        $response = Http::withToken(session('access_token'))->get(env('VITE_API_BASE_URL') . '/api/admin/tracking');
        $rawPoints = $response->json('data') ?? [];
        $technicianPoints = collect($rawPoints)->map(function($p) {
            $isBusy = !empty($p['current_task']);
            // Gunakan koordinat dari database
            $lat = isset($p['tech_latitude']) ? (float)$p['tech_latitude'] : -6.2088;
            $lng = isset($p['tech_longitude']) ? (float)$p['tech_longitude'] : 106.8456;
            
            return [
                'lat' => $lat,
                'lng' => $lng,
                'label' => $p['name'],
                'status' => $isBusy ? 'Bertugas: ' . $p['current_task'] : 'Online (Tersedia)',
                'is_busy' => $isBusy
            ];
        })->toArray();
        return view('admin.main_page', compact('technicianPoints'));
    }

    // Tampilkan dashboard statistik
    public function dashboard()
    {
        $apiBaseUrl = env('VITE_API_BASE_URL');
        $token = session('access_token');


        $techResponse = Http::withToken($token)->get($apiBaseUrl . '/api/admin/technicians');
        $technicians = $techResponse->object()->technicians ?? [];
        $totalTechnicians = count($technicians);


        $tasksResponse = Http::withToken($token)->get($apiBaseUrl . '/api/admin/tasks');
        $allTasks = collect($tasksResponse->object()->tasks ?? []);

        $completedTasks = $allTasks->where('status', 'completed')->count();
        $pendingTasks = $allTasks->where('status', 'assigned')->count();
        $progressTasks = $allTasks->whereIn('status', ['accepted', 'on-going'])->count();
        $totalTasks = $allTasks->count();


        $activeTasks = $allTasks->whereIn('status', ['assigned', 'accepted', 'on-going'])->values();

        return view('admin.dashboard', compact(
            'totalTechnicians', 
            'completedTasks', 
            'pendingTasks', 
            'progressTasks', 
            'totalTasks',
            'activeTasks'
        ));
    }

    // Tampilkan daftar teknisi
    public function technicians()
    {
        $response = Http::withToken(session('access_token'))->get(env('VITE_API_BASE_URL') . '/api/admin/technicians');
        $technicians = $response->object()->technicians ?? [];
        return view('admin.technicians', ['technicians' => collect($technicians)]);
    }

    // Tampilkan daftar task
    public function tasks()
    {
        $response = Http::withToken(session('access_token'))->get(env('VITE_API_BASE_URL') . '/api/admin/technicians');
        $technicians = $response->object()->technicians ?? [];
        return view('admin.tasks', ['technicians' => collect($technicians)]);
    }

    // Tampilkan tracking map
    public function tracking()
    {
        return view('admin.tracking');
    }

    // Tampilkan riwayat task
    public function history()
    {
        $response = Http::withToken(session('access_token'))->get(env('VITE_API_BASE_URL') . '/api/admin/tasks');
        $tasks = collect($response->object()->tasks ?? [])->filter(function($task) {
            return in_array($task->status ?? '', ['completed', 'canceled', 'rejected']);
        })->values();
        return view('admin.history', compact('tasks'));
    }

    // Tampilkan galeri foto
    public function photoLibrary()
    {
        return view('admin.photo_library');
    }

    // Unduh laporan
    public function downloadReport()
    {
        return redirect(env('VITE_API_BASE_URL') . '/api/admin/report/download');
    }

    // Unduh CSV riwayat
    public function downloadHistory(Request $request)
    {
        $queryString = http_build_query($request->all());
        return redirect(env('VITE_API_BASE_URL') . '/api/admin/history/download?' . $queryString);
    }

    // Ambil detail riwayat
    public function historyDetail($id)
    {
        $apiBaseUrl = env('VITE_API_BASE_URL');
        $token = session('access_token');

        $response = Http::withToken($token)->get($apiBaseUrl . '/api/admin/tasks');
        $tasks = collect($response->object()->tasks ?? []);
        
        $task = $tasks->firstWhere('id', (int)$id);

        if (!$task) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        // Format actions ke array
        $actions = [];
        if (!empty($task->actions)) {
            $decoded = is_string($task->actions) ? json_decode($task->actions, true) : $task->actions;
            if (is_array($decoded)) {
                $actions = $decoded;
            } elseif (is_string($task->actions) && !empty($task->actions)) {
                $actions = [$task->actions];
            }
        }

        // Format proofs
        $photos = [];
        if (!empty($task->proofs)) {
            foreach ($task->proofs as $proof) {
                $proof = (object) $proof;
                $rawPath = $proof->photo_path;
                
                // Bersihkan path foto
                $cleanPath = ltrim($rawPath, '/');
                $cleanPath = str_replace('public/', '', $cleanPath);
                $cleanPath = str_replace('storage/', '', $cleanPath);
                $cleanPath = ltrim($cleanPath, '/');

                $photoUrl = rtrim($apiBaseUrl, '/') . '/storage/' . $cleanPath;
                
                // Konversi ke Base64 untuk PDF
                $base64 = null;
                try {
                    $imgResponse = Http::timeout(3)->get($photoUrl);
                    if ($imgResponse->successful()) {
                        $imgType = $imgResponse->header('Content-Type') ?: 'image/jpeg';
                        $base64 = 'data:' . $imgType . ';base64,' . base64_encode($imgResponse->body());
                    }
                } catch (\Exception $e) {

                }

                $photos[] = [
                    'url'    => $photoUrl,
                    'base64' => $base64,
                    'note'   => $proof->note ?? 'Bukti Pekerjaan'
                ];
            }
        }

        // Format tanggal selesai 
        $rawDate = $task->completed_at ?? null;
        $completedAtFormatted = $rawDate 
            ? \Illuminate\Support\Carbon::parse($rawDate)->format('d M Y . H : i') 
            : '-';

        // Format tanggal tugas diberikan
        $rawCreatedAt = $task->created_at ?? null;
        $createdAtFormatted = $rawCreatedAt
            ? \Illuminate\Support\Carbon::parse($rawCreatedAt)->format('d M Y . H : i')
            : '-';

        $formattedData = [
            'id'               => $task->id,
            'title'            => $task->title,
            'status'           => $task->status,
            'customer_name'    => $task->customer_name,
            'customer_phone'   => $task->customer_phone,
            'address'          => $task->address,
            'created_at'       => $createdAtFormatted,
            'completed_at'     => $completedAtFormatted,
            'technician_name'  => $task->technician->name ?? '-',
            'technician_phone' => $task->technician->phone ?? '-',
            'technician_email' => $task->technician->email ?? '-',
            'actions'          => $actions,
            'catatan'          => $task->catatan ?? 'Tidak ada catatan',
            'photos'           => $photos
        ];

        return response()->json($formattedData);
    }
}