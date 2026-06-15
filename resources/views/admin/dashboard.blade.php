@extends('admin.layout')

@section('content')
@vite(['resources/css/dashboard.css'])

<div class="dashboard-page">

    <div class="page-header">
        <h1 class="page-title">Dashboard Admin</h1>
        <p class="page-subtitle">Ringkasan Pekerjaan Teknisi Hari Ini</p>
    </div>

    <div class="card-container">
        <div class="card">
            <div class="card-icon purple">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="card-info">
                <div class="card-title">Total Teknisi</div>
                <div class="card-number">{{ $totalTechnicians ?? 0 }}</div>
            </div>
        </div>

        <div class="card">
            <div class="card-icon green">
                <i class="bi bi-check-lg"></i>
            </div>
            <div class="card-info">
                <div class="card-title">Selesai</div>
                <div class="card-number">{{ $completedTasks ?? 0 }}</div>
            </div>
        </div>

        <div class="card">
            <div class="card-icon orange">
                <i class="bi bi-clock"></i>
            </div>
            <div class="card-info">
                <div class="card-title">Pending</div>
                <div class="card-number">{{ $pendingTasks ?? 0 }}</div>
            </div>
        </div>

        <div class="card">
            <div class="card-icon yellow">
                <i class="bi bi-arrow-repeat"></i>
            </div>
            <div class="card-info">
                <div class="card-title">Progress</div>
                <div class="card-number">{{ $progressTasks ?? 0 }}</div>
            </div>
        </div>

        <div class="card">
            <div class="card-icon pink">
                <i class="bi bi-briefcase-fill"></i>
            </div>
            <div class="card-info">
                <div class="card-title">Total Pekerjaan</div>
                <div class="card-number">{{ $totalTasks ?? 0 }}</div>
            </div>
        </div>
    </div>

    <div class="table-box">
        <div class="table-header">Pekerja Aktif (Progress)</div>

        <table>
            <thead>
                <tr>
                    <th>ID Pekerja</th>
                    <th>Jenis Pekerjaan</th>
                    <th>Teknisi</th>
                    <th>Lokasi</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse($activeTasks ?? [] as $task)
                @php
                    if ($task->status == 'completed') {
                        $statusClass = 'status-completed';
                        $statusText = 'Completed';
                    } elseif ($task->status == 'on-going') {
                        $statusClass = 'status-ongoing';
                        $statusText = 'On-going';
                    } elseif ($task->status == 'assigned') {
                        $statusClass = 'status-assigned';
                        $statusText = 'Assigned';
                    } elseif ($task->status == 'accepted') {
                        $statusClass = 'status-accepted';
                        $statusText = 'Accepted';
                    } else {
                        $statusClass = 'status-assigned';
                        $statusText = 'Assigned';
                    }
                @endphp
                <tr>
                    <td>NET-2405-{{ str_pad($task->id, 4, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $task->title ?? '-' }}</td>
                    <td>{{ $task->technician->name ?? '-' }}</td>
                    <td>{{ $task->address ?? '-' }}</td>
                    <td>
                        <span class="status {{ $statusClass }}">{{ $statusText }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px;">
                        <i class="bi bi-inbox" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 10px;"></i>
                        Belum ada data pekerjaan aktif.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="footer-link">
            <a href="{{ route('admin.tracking') }}">
                Lihat Semua Pekerja Aktif <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</div>
@endsection