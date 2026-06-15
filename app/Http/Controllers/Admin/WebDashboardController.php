<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WebDashboardController extends Controller
{
    public function mainPage()
    {
        $response = Http::withToken(session('access_token'))->get(env('VITE_API_BASE_URL') . '/api/admin/tracking');
        $rawPoints = $response->json('data') ?? [];
        $technicianPoints = collect($rawPoints)->map(function($p) {
            return [
                'lat' => $p['tech_latitude'],
                'lng' => $p['tech_longitude'],
                'label' => $p['name'],
                'status' => $p['current_task'] ?? 'On Duty'
            ];
        })->toArray();
        return view('admin.main_page', compact('technicianPoints'));
    }

    public function dashboard()
    {
        $apiBaseUrl = env('VITE_API_BASE_URL');
        $token = session('access_token');

        // Fetch Technicians for count
        $techResponse = Http::withToken($token)->get($apiBaseUrl . '/api/admin/technicians');
        $technicians = $techResponse->object()->technicians ?? [];
        $totalTechnicians = count($technicians);

        // Fetch All Tasks for statistics
        $tasksResponse = Http::withToken($token)->get($apiBaseUrl . '/api/admin/tasks');
        $allTasks = collect($tasksResponse->object()->tasks ?? []);

        $completedTasks = $allTasks->where('status', 'completed')->count();
        $pendingTasks = $allTasks->where('status', 'assigned')->count();
        $progressTasks = $allTasks->whereIn('status', ['accepted', 'on-going'])->count();
        $totalTasks = $allTasks->count();

        // Active Tasks (Progress) for the table
        $activeTasks = $allTasks->whereIn('status', ['accepted', 'on-going'])->values();

        return view('admin.dashboard', compact(
            'totalTechnicians', 
            'completedTasks', 
            'pendingTasks', 
            'progressTasks', 
            'totalTasks',
            'activeTasks'
        ));
    }

    public function technicians()
    {
        $response = Http::withToken(session('access_token'))->get(env('VITE_API_BASE_URL') . '/api/admin/technicians');
        $technicians = $response->object()->technicians ?? [];
        return view('admin.technicians', ['technicians' => collect($technicians)]);
    }

    public function tasks()
    {
        $response = Http::withToken(session('access_token'))->get(env('VITE_API_BASE_URL') . '/api/admin/technicians');
        $technicians = $response->object()->technicians ?? [];
        return view('admin.tasks', ['technicians' => collect($technicians)]);
    }

    public function tracking()
    {
        return view('admin.tracking');
    }

    public function history()
    {
        $response = Http::withToken(session('access_token'))->get(env('VITE_API_BASE_URL') . '/api/admin/tasks');
        $tasks = collect($response->object()->tasks ?? [])->filter(function($task) {
            return in_array($task->status ?? '', ['completed', 'canceled']);
        })->values();
        return view('admin.history', compact('tasks'));
    }

    public function photoLibrary()
    {
        return view('admin.photo_library');
    }

    public function downloadReport()
    {
        return redirect(env('VITE_API_BASE_URL') . '/api/admin/report/download');
    }

    public function downloadHistory(Request $request)
    {
        $queryString = http_build_query($request->all());
        return redirect(env('VITE_API_BASE_URL') . '/api/admin/history/download?' . $queryString);
    }

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

        // Format tindakan (actions) menjadi array
        $actions = [];
        if (!empty($task->actions)) {
            $decoded = is_string($task->actions) ? json_decode($task->actions, true) : $task->actions;
            if (is_array($decoded)) {
                $actions = $decoded;
            } elseif (is_string($task->actions) && !empty($task->actions)) {
                $actions = [$task->actions];
            }
        }

        // Map foto dari database (proofs) - Support format cPanel & Lokal
        $photos = [];
        if (!empty($task->proofs)) {
            foreach ($task->proofs as $proof) {
                $proof = (object) $proof;
                $rawPath = $proof->photo_path;
                
                // Bersihkan path dari prefix cPanel yang sering muncul
                $cleanPath = ltrim($rawPath, '/');
                $cleanPath = str_replace('public/', '', $cleanPath);
                $cleanPath = str_replace('storage/', '', $cleanPath);
                $cleanPath = ltrim($cleanPath, '/');

                $photoUrl = rtrim($apiBaseUrl, '/') . '/storage/' . $cleanPath;
                
                $photos[] = [
                    'url'  => $photoUrl,
                    'note' => $proof->note ?? 'Bukti Pekerjaan'
                ];
            }
        }

        // Format tanggal selesai agar serasi dengan UI original
        $rawDate = $task->completed_at ?? $task->updated_at ?? null;
        $completedAtFormatted = $rawDate 
            ? \Illuminate\Support\Carbon::parse($rawDate)->format('d M Y . H : i') 
            : '-';

        $formattedData = [
            'id'               => $task->id,
            'title'            => $task->title,
            'status'           => $task->status,
            'customer_name'    => $task->customer_name,
            'customer_phone'   => $task->customer_phone,
            'address'          => $task->address,
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