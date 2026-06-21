@extends('admin.layout')

@section('content')
@vite(['resources/css/history.css', 'resources/js/history.js'])

{{-- INJECT SEARCH KE TOPBAR --}}
@push('topbar_left')
<div class="topbar-search">
    <i class="bi bi-search"></i>
    <input type="text" id="historySearchInput" placeholder="Cari history..." oninput="searchHistory(this.value)">
</div>
@endpush

<div class="history-page">
    <div class="history-header">
        <h1 class="history-title">History</h1>
        <p class="history-subtitle">check history dengan mudah dan cepat</p>
    </div>
    
    <div class="filter-section">
        <div class="filter-buttons">
            <button class="filter-btn filter-btn-all active" onclick="filterHistory('all', this)">ALL</button>
            <button class="filter-btn filter-btn-finished" onclick="filterHistory('finished', this)">Finished</button>
            <button class="filter-btn filter-btn-canceled" onclick="filterHistory('canceled', this)">Canceled</button>
        </div>
        
        <div class="download-dropdown">
            <button class="download-btn" onclick="toggleDownloadDropdown()">
                <i class="bi bi-download"></i> Unduh <i class="bi bi-chevron-down"></i>
            </button>
            <div class="dropdown-menu" id="downloadDropdown">
                <a href="#" onclick="downloadReport('csv')">
                    <i class="bi bi-filetype-csv"></i> Unduh CSV
                </a>
                <a href="#" onclick="downloadReport('monthly')">
                    <i class="bi bi-calendar-month"></i> Unduh Perbulan (CSV)
                </a>
                <a href="#" onclick="downloadReport('date')">
                    <i class="bi bi-calendar-date"></i> Unduh Pertanggal (CSV)
                </a>
            </div>
        </div>
    </div>
    
    <div class="history-container">
        <div class="history-grid" id="historyGrid">
            @forelse($tasks as $task)
            @php
                $badgeClass = 'badge-finished';
                $badgeText = 'Finished';
                $taskStatus = 'completed';
                
                if(isset($task->status) && ($task->status === 'canceled' || $task->status === 'rejected')) {
                    $badgeClass = 'badge-canceled';
                    $badgeText = 'Canceled';
                    $taskStatus = 'canceled';
                }
            @endphp
            
            <div class="history-card" 
                 data-status="{{ $taskStatus }}" 
                 data-id="{{ $task->id }}"
                 data-search="{{ strtolower($task->title . ' ' . ($task->customer_name ?? '') . ' ' . $task->address . ' TK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT)) }}"
                 onclick="showJobDetails({{ $task->id }})">
                <div class="card-badge {{ $badgeClass }}">{{ $badgeText }}</div>
                
                <div class="card-top">
                    <div class="card-avatar">
                        <i class="bi bi-tools"></i>
                    </div>
                    <div class="card-info">
                        <div class="card-id">TK-{{ str_pad($task->id, 4, '0', STR_PAD_LEFT) }}</div>
                        <div class="card-title">{{ $task->title }}</div>
                        <div class="card-customer">{{ $task->customer_name ?? 'Pelanggan' }}</div>
                    </div>
                </div>
                
                <div class="card-location">
                    <i class="bi bi-geo-alt-fill"></i>
                    {{ $task->address }}
                </div>
                
                <div class="card-footer">
                    <span class="card-date">
                        <i class="bi bi-calendar3"></i>
                        {{ \Carbon\Carbon::parse($task->completed_at ?? $task->updated_at, 'UTC')->timezone('Asia/Jakarta')->format('d M Y . H : i') }}
                    </span>
                    <span class="card-tech">
                        <i class="bi bi-person-circle"></i>
                        {{ $task->technician_name ?? $task->technician->name ?? 'Teknisi' }}
                    </span>
                </div>
            </div>
            @empty
            <div class="empty-history">
                <i class="bi bi-clock-history"></i>
                <p>Belum ada riwayat pekerjaan</p>
            </div>
            @endforelse
        </div>
        
        <div class="job-details-panel" id="jobDetailsPanel">
            <div class="panel-header">
                <h3>History Details</h3>
                <button class="close-panel" onclick="closeJobDetails()">&times;</button>
            </div>
            
            <div class="panel-summary" id="panelSummary" style="display:none;">

            </div>
            
            <div class="panel-body" id="jobDetailsContent">
                <div class="empty-detail">
                    <i class="bi bi-folder2-open"></i>
                    <p>Klik pada kartu history untuk melihat detail</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="shareModal" class="share-modal">
    <div class="share-modal-content">
        <div class="share-modal-header">
            <h3><i class="bi bi-share-fill"></i> Share Report</h3>
            <span class="close" onclick="closeShareModal()">&times;</span>
        </div>
        <div class="share-modal-body">
            <p>Bagikan laporan pekerjaan ini</p>
            <div class="share-options">
                <button class="share-btn share-btn-whatsapp" onclick="shareViaWhatsApp()">
                    <i class="bi bi-whatsapp"></i> WhatsApp
                </button>
                <button class="share-btn share-btn-email" onclick="shareViaEmail()">
                    <i class="bi bi-envelope-fill"></i> Email
                </button>
                <button class="share-btn share-btn-pdf" onclick="savePdf()">
                    <i class="bi bi-file-earmark-pdf-fill"></i> Simpan 
                </button>
            </div>
        </div>
    </div>
</div>

<div id="datePickerModal" class="datepicker-modal">
    <div class="datepicker-content">
        <div class="datepicker-header">
            <h3 id="datePickerTitle"><i class="bi bi-calendar3"></i> Pilih Tanggal</h3>
            <span class="datepicker-close" onclick="closeDatePicker()">&times;</span>
        </div>
        <div class="datepicker-body">
            <div class="datepicker-nav">
                <button class="dp-nav-btn" onclick="dpChangeMonth(-1)"><i class="bi bi-chevron-left"></i></button>
                <span class="dp-month-label" id="dpMonthLabel">Mei 2026</span>
                <button class="dp-nav-btn" onclick="dpChangeMonth(1)"><i class="bi bi-chevron-right"></i></button>
            </div>
            <div id="dpMonthGrid" class="dp-month-grid" style="display:none;">
                <div class="dp-month-names">
                    <button onclick="dpSelectMonth(0)">Jan</button>
                    <button onclick="dpSelectMonth(1)">Feb</button>
                    <button onclick="dpSelectMonth(2)">Mar</button>
                    <button onclick="dpSelectMonth(3)">Apr</button>
                    <button onclick="dpSelectMonth(4)">Mei</button>
                    <button onclick="dpSelectMonth(5)">Jun</button>
                    <button onclick="dpSelectMonth(6)">Jul</button>
                    <button onclick="dpSelectMonth(7)">Agu</button>
                    <button onclick="dpSelectMonth(8)">Sep</button>
                    <button onclick="dpSelectMonth(9)">Okt</button>
                    <button onclick="dpSelectMonth(10)">Nov</button>
                    <button onclick="dpSelectMonth(11)">Des</button>
                </div>
            </div>

            <div id="dpCalendarGrid" class="dp-calendar-grid">
                <div class="dp-weekdays">
                    <span>MIN</span><span>SEN</span><span>SEL</span>
                    <span>RAB</span><span>KAM</span><span>JUM</span><span>SAB</span>
                </div>
                <div class="dp-days" id="dpDaysGrid"></div>
            </div>
        </div>
        <div class="datepicker-footer">
            <button class="dp-cancel-btn" onclick="closeDatePicker()">Batal</button>
            <button class="dp-confirm-btn" id="dpConfirmBtn" onclick="dpConfirm()" disabled>Unduh</button>
        </div>
    </div>
</div>
@endsection