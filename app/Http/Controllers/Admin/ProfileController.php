<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProfileController extends Controller
{
    public function index()
    {
        $response = Http::withToken(session('access_token'))->get(env('VITE_API_BASE_URL') . '/api/auth/me');
        $user = $response->object()->user ?? (object) (session('user') ?? []);
        
        return view('admin.profile', [
            'user' => $user
        ]);
    }
    
    public function update(Request $request)
    {
        $apiBaseUrl = env('VITE_API_BASE_URL');
        $token = session('access_token');

        // Gunakan multipart untuk semua data agar konsisten
        $multipart = [];
        $fields = ['name', 'email', 'phone', 'password', 'password_confirmation'];
        
        foreach ($fields as $field) {
            if ($request->filled($field)) {
                $multipart[] = [
                    'name'     => $field,
                    'contents' => $request->get($field)
                ];
            }
        }

        if ($request->hasFile('avatar')) {
            $multipart[] = [
                'name'     => 'avatar',
                'contents' => fopen($request->file('avatar')->getPathname(), 'r'),
                'filename' => $request->file('avatar')->getClientOriginalName()
            ];
        }

        // Kirim request POST dengan spoofing PUT
        $response = Http::withToken($token)
            ->asMultipart()
            ->post($apiBaseUrl . '/api/auth/profile?_method=PUT', $multipart);
        
        if ($response->successful()) {
            $userData = $response->json('user');
            if ($userData) {
                session(['user' => (object) $userData]);
            }
            return redirect()->route('admin.profile')->with('success', 'Profil berhasil diperbarui!');
        }
        
        $errorData = $response->json();
        $msg = 'Gagal memperbarui profil.';
        if (isset($errorData['errors'])) {
            $msg = collect($errorData['errors'])->flatten()->first();
        } elseif (isset($errorData['message'])) {
            $msg = $errorData['message'];
        }
        
        return redirect()->back()->with('error', $msg)->withInput();
    }
}