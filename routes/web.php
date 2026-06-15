<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\WebDashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\Admin\TechnicianController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/forgot-password', [AuthController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');

Route::get('/reset-password', function (Illuminate\Http\Request $request) {
    $token = $request->query('token');
    $email = $request->query('email');
    if (!$token || !$email) {
        abort(404);
    }
    return redirect()->route('password.reset', ['token' => $token, 'email' => $email]);
});

Route::get('/proxy-nominatim', function (Request $req) {
    $query = $req->get('q');
    $allowedDomains = ['nominatim.openstreetmap.org'];
})->middleware('auth');

Route::prefix('admin')->middleware(['api.auth'])->group(function () {

    Route::get('/main-page', [WebDashboardController::class, 'mainPage'])
        ->name('admin.main-page');
    Route::get('/main-page/report/download', [WebDashboardController::class, 'downloadReport'])
        ->name('admin.report.download');
    Route::get('/main-page/photo-library', [WebDashboardController::class, 'photoLibrary'])
        ->name('admin.photo.library');

    Route::get('/dashboard', [WebDashboardController::class, 'dashboard'])
        ->name('admin.dashboard');

    Route::get('/technicians', [WebDashboardController::class, 'technicians'])
        ->name('admin.technicians');
    Route::get('/technicians/create', [TechnicianController::class, 'create'])
        ->name('admin.technicians.create');
    Route::post('/technicians', [TechnicianController::class, 'store'])
        ->name('admin.technicians.store');
    Route::put('/technicians/{id}', [TechnicianController::class, 'update'])
        ->name('admin.technicians.update');
    Route::delete('/technicians/{id}', [TechnicianController::class, 'destroy'])
        ->name('admin.technicians.destroy');

    Route::get('/tasks', [WebDashboardController::class, 'tasks'])
        ->name('admin.tasks');
    Route::post('/tasks/store', [TaskController::class, 'store'])
        ->name('admin.tasks.store');
    Route::put('/tasks/{id}', [TaskController::class, 'update'])
        ->name('admin.tasks.update');
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy'])
        ->name('admin.tasks.destroy');

    Route::get('/tracking', [WebDashboardController::class, 'tracking'])
        ->name('admin.tracking');

    Route::get('/history', [WebDashboardController::class, 'history'])
        ->name('admin.history');
    Route::get('/history/download', [WebDashboardController::class, 'downloadHistory'])
        ->name('admin.history.download');
    Route::get('/history/detail/{id}', [WebDashboardController::class, 'historyDetail'])
        ->name('admin.history.detail');

    Route::get('/profile', [ProfileController::class, 'index'])
        ->name('admin.profile');
    Route::put('/profile', [ProfileController::class, 'update'])
        ->name('admin.profile.update');
    Route::put('/profile/avatar', [ProfileController::class, 'updateAvatar'])
        ->name('admin.profile.avatar');

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('admin.notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('admin.notifications.readAll');
    Route::post('/notifications/clear-all', [NotificationController::class, 'clearAll'])
        ->name('admin.notifications.clearAll');
    Route::post('/tasks/{task}/respond', [NotificationController::class, 'respond'])
        ->name('admin.tasks.respond');
});