<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;

class AuthController extends Controller
{
    protected $apiUrl;

    public function __construct()
    {
        $this->apiUrl = env('VITE_API_BASE_URL', 'http://api.mavii.test');
    }

    public function showLogin()
    {
        if (session()->has('access_token')) {
            return redirect()->route('admin.main-page');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        try {
            $client = new Client();
            $response = $client->post($this->apiUrl . '/api/auth/login', [
                'json' => [
                    'email' => $request->email,
                    'password' => $request->password,
                ],
                'http_errors' => false
            ]);

            $body = json_decode($response->getBody(), true);

            if ($response->getStatusCode() === 200 && (isset($body['token']) || isset($body['access_token']))) {
                $token = $body['token'] ?? $body['access_token'];
                // Simpan token dan data user ke session
                session([
                    'access_token' => $token,
                    'user' => $body['user'] ?? null
                ]);

                Log::info('Login sukses via API', ['email' => $request->email]);
                return redirect()->route('admin.main-page');
            }

            return back()->withErrors([
                'email' => $body['message'] ?? 'Email atau password salah.',
            ]);

        } catch (\Exception $e) {
            Log::error('Koneksi ke API Backend gagal', ['error' => $e->getMessage()]);
            return back()->withErrors([
                'email' => 'Gagal terhubung ke server backend.',
            ]);
        }
    }

    public function logout(Request $request)
    {
        session()->forget(['access_token', 'user']);
        return redirect('/login');
    }

    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $client = new Client();
            $response = $client->post($this->apiUrl . '/api/auth/forgot-password', [
                'json' => ['email' => $request->email],
                'http_errors' => false
            ]);

            $body = json_decode($response->getBody(), true);

            if ($response->getStatusCode() === 200) {
                return back()->with('status', $body['message'] ?? 'Link reset password telah dikirim ke email Anda.');
            }

            return back()->withErrors(['email' => $body['message'] ?? 'Gagal mengirim link reset password.']);

        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Gagal terhubung ke server backend.']);
        }
    }

    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        try {
            $client = new Client();
            $response = $client->post($this->apiUrl . '/api/auth/reset-password', [
                'json' => [
                    'token' => $request->token,
                    'email' => $request->email,
                    'password' => $request->password,
                    'password_confirmation' => $request->password_confirmation,
                ],
                'http_errors' => false
            ]);

            $body = json_decode($response->getBody(), true);

            if ($response->getStatusCode() === 200) {
                return redirect()->route('login')->with('status', 'Password berhasil direset. Silakan login.');
            }

            return back()->withErrors(['email' => $body['message'] ?? 'Gagal meriset password.']);

        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Gagal terhubung ke server backend.']);
        }
    }
}