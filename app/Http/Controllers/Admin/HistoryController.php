<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HistoryController extends Controller
{
    public function index()
    {
        $tasks = Task::with('technician')
            ->whereIn('status', ['completed', 'rejected'])
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('admin.history', compact('tasks'));
    }

    public function detail($id)
    {
        $task = Task::with(['technician', 'proofs'])->findOrFail($id);

        $photos = $task->proofs->map(fn($p) => [
            'url'  => asset('storage/' . $p->photo_path),
            'note' => $p->note,
        ])->toArray();

        return response()->json([
            'id'               => $task->id,
            'title'            => $task->title,
            'description'      => $task->description,
            'status'           => $task->status,
            'address'          => $task->address,
            'customer_name'    => $task->customer_name,
            'customer_phone'   => $task->customer_phone,
            'technician_name'  => $task->technician->name ?? '-',
            'technician_phone' => $task->technician->phone ?? '-',
            'technician_email' => $task->technician->email ?? '-',
            'actions'          => $task->actions ?? [],
            'catatan'          => $task->catatan ?? $task->notes ?? null,
            'photos'           => $photos,
            'completed_at'     => $task->completed_at
                ? Carbon::parse($task->completed_at)->format('d M Y . H : i')
                : Carbon::parse($task->updated_at)->format('d M Y . H : i'),
        ]);
    }

    public function export(Request $request)
    {
        $query = Task::with('technician')
            ->whereIn('status', ['completed', 'rejected']);

        if ($request->type === 'monthly' && $request->month && $request->year) {
            $query->whereMonth('updated_at', $request->month)
                  ->whereYear('updated_at', $request->year);
            $filename = "history-{$request->year}-{$request->month}.csv";
        }
        elseif ($request->type === 'date' && $request->date) {
            $query->whereDate('updated_at', $request->date);
            $filename = "history-{$request->date}.csv";
        }
        else {
            $filename = 'history-semua.csv';
        }

        $tasks = $query->orderByDesc('updated_at')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ];

        $callback = function () use ($tasks) {
            $file = fopen('php://output', 'w');

            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'ID',
                'Kode Tugas',
                'Judul / Jenis Gangguan',
                'Deskripsi',
                'Status',
                'Nama Pelanggan',
                'Telepon Pelanggan',
                'Lokasi',
                'Nama Teknisi',
                'Telepon Teknisi',
                'Email Teknisi',
                'Tanggal Selesai',
            ], ';');

            foreach ($tasks as $task) {
                $completedAt = $task->completed_at
                    ? Carbon::parse($task->completed_at)->format('d/m/Y H:i')
                    : Carbon::parse($task->updated_at)->format('d/m/Y H:i');

                $statusLabel = $task->status === 'rejected' ? 'Ditolak' : 'Selesai';

                fputcsv($file, [
                    $task->id,
                    'TK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT),
                    $task->title,
                    $task->description ?? '',
                    $statusLabel,
                    $task->customer_name ?? '',
                    $task->customer_phone ?? '',
                    $task->address ?? '',
                    $task->technician->name ?? $task->technician_name ?? '',
                    $task->technician->phone ?? $task->technician_phone ?? '',
                    $task->technician->email ?? $task->technician_email ?? '',
                    $completedAt,
                ], ';');
            }

            fclose($file);
        };

        \Illuminate\Support\Facades\Log::channel('daily')->info('Ekspor history', [
            'type' => $request->type,
            'user' => auth()->id(),
            'ip' => $request->ip()
        ]);
        return response()->stream($callback, 200, $headers);
    }

    public function pdf($id)
    {
        $task = Task::with('technician')->findOrFail($id);

        $photos = [];
        if ($task->photos) {
            $raw = is_string($task->photos) ? json_decode($task->photos, true) : $task->photos;
            if (is_array($raw)) {
                $photos = array_map(fn($p) => storage_path('app/public/' . $p), $raw);
            }
        }

        $actions = $task->actions ?? [];
        if (is_string($actions)) {
            $actions = json_decode($actions, true) ?? [];
        }

        $statusLabel = $task->status === 'rejected' ? 'Ditolak' : 'Selesai';
        $completedAt = $task->completed_at
            ? Carbon::parse($task->completed_at)->translatedFormat('d F Y, H:i')
            : Carbon::parse($task->updated_at)->translatedFormat('d F Y, H:i');

        $html = $this->buildPdfHtml($task, $photos, $actions, $statusLabel, $completedAt);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait');

        $taskCode = 'TK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT);
        $filename = "laporan-{$taskCode}.pdf";

        return $pdf->download($filename);
    }

    private function buildPdfHtml($task, $photos, $actions, $statusLabel, $completedAt)
    {
        $taskCode    = 'TK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT);
        $techName    = $task->technician->name  ?? $task->technician_name  ?? '-';
        $techPhone   = $task->technician->phone ?? $task->technician_phone ?? '-';
        $techEmail   = $task->technician->email ?? $task->technician_email ?? '-';
        $statusColor = $task->status === 'rejected' ? '#dc3545' : '#28a745';
        $statusBg    = $task->status === 'rejected' ? '#f8d7da' : '#d4edda';

        $actionsHtml = '';
        if (!empty($actions)) {
            foreach ($actions as $a) {
                $actionsHtml .= '<li style="margin-bottom:4px;">• ' . htmlspecialchars($a) . '</li>';
            }
            $actionsHtml = '<ul style="margin:0;padding-left:16px;">' . $actionsHtml . '</ul>';
        } else {
            $actionsHtml = '<span style="color:#bbb;">Tidak ada tindakan</span>';
        }

        $photosHtml = '';
        foreach ($photos as $photoPath) {
            if (file_exists($photoPath)) {
                $type       = mime_content_type($photoPath);
                $base64     = base64_encode(file_get_contents($photoPath));
                $photosHtml .= "<img src='data:{$type};base64,{$base64}' style='width:120px;height:90px;object-fit:cover;border-radius:6px;margin-right:8px;margin-bottom:8px;'>";
            }
        }
        if (empty($photosHtml)) {
            $photosHtml = '<span style="color:#bbb;font-size:12px;">Tidak ada foto</span>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size:13px; color:#222; background:#fff; }
  .header { background:#1b1fb8; color:white; padding:24px 32px; }
  .header h1 { font-size:22px; font-weight:700; margin-bottom:4px; }
  .header p { font-size:12px; opacity:0.85; }
  .body { padding:24px 32px; }
  .section { margin-bottom:20px; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
  .section-title { background:#f3f4f6; padding:10px 16px; font-size:11px; font-weight:700; color:#555; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid #e5e7eb; }
  .section-body { padding:14px 16px; }
  table.info { width:100%; border-collapse:collapse; }
  table.info td { padding:6px 0; font-size:13px; vertical-align:top; }
  table.info td:first-child { width:140px; color:#777; font-weight:600; font-size:12px; }
  .status-box { display:inline-block; padding:4px 14px; border-radius:30px; font-size:11px; font-weight:700; text-transform:uppercase; background:{$statusBg}; color:{$statusColor}; }
  .footer { margin-top:32px; padding-top:16px; border-top:1px solid #eee; font-size:11px; color:#aaa; text-align:center; }
</style>
</head>
<body>

<div class="header">
  <h1>Laporan Pekerjaan</h1>
  <p>MAVII &mdash; Manajemen Asisten Visual Infrastruktur Internet</p>
  <p style="margin-top:8px;">
    <span style="background:rgba(255,255,255,0.15);padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700;">{$taskCode}</span>
    &nbsp;&nbsp;
    <span class="status-box">{$statusLabel}</span>
  </p>
</div>

<div class="body">

  <div class="section">
    <div class="section-title">&#128100; Informasi Pelanggan</div>
    <div class="section-body">
      <table class="info">
        <tr><td>Nama</td><td>{htmlspecialchars($task->customer_name, ENT_QUOTES, 'UTF-8')}</td></tr>
        <tr><td>Telepon</td><td>{htmlspecialchars($task->customer_phone, ENT_QUOTES, 'UTF-8')}</td></tr>
        <tr><td>Lokasi</td><td>{htmlspecialchars($task->address, ENT_QUOTES, 'UTF-8')}</td></tr>
        <tr><td>Tanggal</td><td>{htmlspecialchars($completedAt, ENT_QUOTES, 'UTF-8')}</td></tr>
      </table>
    </div>
  </div>

  <div class="section">
    <div class="section-title">&#128295; Detail Pekerjaan</div>
    <div class="section-body">
      <table class="info">
        <tr><td>Jenis Gangguan</td><td>{htmlspecialchars($task->title, ENT_QUOTES, 'UTF-8')}</td></tr>
        <tr><td>Deskripsi</td><td>{htmlspecialchars($task->description, ENT_QUOTES, 'UTF-8')}</td></tr>
      </table>
    </div>
  </div>

  <div class="section">
    <div class="section-title">&#128100; Teknisi</div>
    <div class="section-body">
      <table class="info">
        <tr><td>Nama</td><td>{htmlspecialchars($techName, ENT_QUOTES, 'UTF-8')}</td></tr>
        <tr><td>Telepon</td><td>{htmlspecialchars($techPhone, ENT_QUOTES, 'UTF-8')}</td></tr>
        <tr><td>Email</td><td>{htmlspecialchars($techEmail, ENT_QUOTES, 'UTF-8')}</td></tr>
      </table>
    </div>
  </div>

  <div class="section">
    <div class="section-title">&#9989; Hasil dan Status</div>
    <div class="section-body">
      <table class="info">
        <tr><td>Tindakan</td><td>{htmlspecialchars($actionsHtml, ENT_QUOTES, 'UTF-8')}</td></tr>
        <tr><td>Catatan</td><td>{htmlspecialchars($task->catatan, ENT_QUOTES, 'UTF-8')}</td></tr>
        <tr><td>Bukti Foto</td><td>{htmlspecialchars($photosHtml, ENT_QUOTES, 'UTF-8')}</td></tr>
        <tr><td>Status</td><td><span class="status-box">{$statusLabel}</span></td></tr>
        <tr><td>Tanggal</td><td>{htmlspecialchars($completedAt, ENT_QUOTES, 'UTF-8')}</td></tr>
      </table>
    </div>
  </div>

</div>

<div class="footer">
  Dibuat oleh: MAVII Field Service Management System &bull; {htmlspecialchars($completedAt, ENT_QUOTES, 'UTF-8')}
</div>

</body>
</html>
HTML;
    }
}