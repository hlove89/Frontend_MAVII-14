<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class NotificationController extends Controller
{
    public function index()
    {
        $apiBaseUrl = env('VITE_API_BASE_URL');
        $token = session('access_token');

        if (!$token) {
            return response()->json(['notifications' => [], 'unread_count' => 0]);
        }

        $response = Http::withToken($token)->get($apiBaseUrl . '/api/notifications');
        
        if (!$response->successful()) {
            return response()->json([
                'notifications' => [],
                'unread_count'  => 0,
            ]);
        }

        $data = $response->json('notifications') ?? [];
        $notifications = collect($data)->map(function ($n) {
            $n = (object) $n;
            
            // Try to extract technician info from data if it exists as JSON
            $extraData = is_string($n->data) ? json_decode($n->data, true) : ($n->data ?? []);
            $techName = $extraData['technician_name'] ?? ($n->title ?? 'System');

            return [
                'id'              => $n->id ?? null,
                'task_id'         => $extraData['task_id'] ?? null,
                'technician_name' => $techName,
                'message'         => $n->message ?? '',
                'status'          => $n->type ?? 'info',
                'unread'          => !($n->is_read ?? false),
                'time_ago'        => isset($n->created_at) ? Carbon::parse($n->created_at)->diffForHumans() : '-',
            ];
        });

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $notifications->where('unread', true)->count(),
        ]);
    }

    public function readAll()
    {
        $apiBaseUrl = env('VITE_API_BASE_URL');
        $token = session('access_token');
        
        $response = Http::withToken($token)->post($apiBaseUrl . '/api/notifications/read-all');
        
        if ($response->successful()) {
            return response()->json(['message' => 'Semua notifikasi sudah dibaca.']);
        }
        return response()->json(['message' => 'Gagal menandai notifikasi.'], 500);
    }

    public function clearAll()
    {
        $apiBaseUrl = env('VITE_API_BASE_URL');
        $token = session('access_token');
        
        $response = Http::withToken($token)->delete($apiBaseUrl . '/api/notifications');
        
        if ($response->successful()) {
            return response()->json(['message' => 'Semua notifikasi berhasil dihapus.']);
        }
        return response()->json(['message' => 'Gagal menghapus notifikasi.'], 500);
    }
}