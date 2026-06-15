@extends('admin.layout')

@section('content')
<link rel="stylesheet" href="{{ asset('css/tasks.css') }}">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

@php
    $onlineTechnicians = $technicians->filter(function($t) {
        return isset($t->is_online) && $t->is_online;
    });
    $adaYangTampil = $onlineTechnicians->isNotEmpty();
@endphp

<div class="tasks-page">
    <div class="tasks-header">
        <h1 class="page-title">Task Management</h1>
        <p class="page-subtitle">buat dan tugaskan pekerjaan baru dengan mudah</p>
    </div>

    @if(session('success')) <meta name="flash-success" content="{{ session('success') }}"> @endif
    @if(session('error')) <meta name="flash-error" content="{{ session('error') }}"> @endif

    <div class="tasks-grid">
        <div class="technicians-section">
            <div class="technicians-grid">
                @forelse($onlineTechnicians as $technician)
                    @php
                        $hasActiveTask = collect($technician->tasks ?? [])->contains(function($t) {
                            return in_array($t->status, ['accepted', 'on-going']);
                        });
                        $cardBg = $hasActiveTask ? '#F24444' : '#C8FF80';
                        $cardStatus = $hasActiveTask ? 'busy' : 'available';
                    @endphp
                    <div class="technician-card" style="background:{{ $cardBg }}" data-id="{{ $technician->id }}" data-name="{{ $technician->name }}" data-status="{{ $cardStatus }}">
                        <div class="card-top">
                            <div class="technician-avatar">
                                @php
                                    $imgUrl = '';
                                    if(!empty($technician->avatar)) {
                                        if(Str::startsWith($technician->avatar, 'http')) {
                                            $imgUrl = $technician->avatar;
                                        } else {
                                            $purePath = ltrim(str_replace('storage/', '', ltrim($technician->avatar, '/')), '/');
                                            $imgUrl = rtrim(env('VITE_API_BASE_URL'), '/') . '/storage/' . $purePath;
                                        }
                                    }
                                @endphp
                                @if($imgUrl)
                                    <img src="{{ $imgUrl }}" alt="{{ $technician->name }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="default-avatar" style="display:none;"><i class="bi bi-person-circle"></i></div>
                                @else
                                    <div class="default-avatar"><i class="bi bi-person-circle"></i></div>
                                @endif
                            </div>
                            <div class="technician-name">{{ $technician->name }}</div>
                        </div>
                        <div class="card-divider"></div>
                        <div class="card-bottom">
                            @if(!$hasActiveTask)
                            <button class="btn-add" onclick="event.stopPropagation(); selectTechnician(this)">Add</button>
                            @else
                            <span class="btn-busy">Sedang Bertugas</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <i class="bi bi-wifi-off"></i>
                        <p>Tidak ada teknisi yang sedang online</p>
                        <p class="empty-sub">Teknisi akan muncul saat mereka online</p>
                    </div>
                @endforelse
            </div>
            @if($adaYangTampil)
            <div class="legend-box">
                <p>⚠️ Box berwarna <span style="color:#C8FF80">Hijau</span> menandakan teknisi tersedia dan box berwarna 
                <span style="color:#F24444">merah</span> menandakan teknisi sedang bertugas</p>
            </div>
            @endif
        </div>

        @if($adaYangTampil)
        <div class="form-section" style="display:none">
            <div class="form-header" id="formHeader" style="display:none"></div>
            <form action="{{ route('admin.tasks.store') }}" method="POST" class="task-form" style="display:none">
                @csrf
                <div class="form-group">
                    <label>Nama :</label>
                    <input type="text" name="customer_name" class="form-control" placeholder="Nama pelanggan" required>
                </div>
                <div class="form-group">
                    <label>Telepon :</label>
                    <input type="text" name="customer_phone" class="form-control" placeholder="Nomor telepon pelanggan">
                </div>
                <div class="form-group">
                    <label>Alamat :</label>
                    <div class="loc-card">
                        <div class="loc-row" onclick="openAddressSheet()">
                            <div class="dot-wrap"><div class="dot"></div></div>
                            <div class="loc-texts">
                                <div class="loc-main ph" id="loc-main">Pilih lokasi pekerjaan</div>
                                <div class="loc-sub" id="loc-sub"></div>
                            </div>
                            <span class="chevron">›</span>
                        </div>
                    </div>
                    <input type="hidden" name="address" id="address-hidden" required>
                    <input type="hidden" name="latitude" id="lat-hidden">
                    <input type="hidden" name="longitude" id="lng-hidden">
                </div>
                <div class="form-group">
                    <label>Jenis Gangguan :</label>
                    <textarea name="title" class="form-control" placeholder="Jenis gangguan / pekerjaan" rows="3" required></textarea>
                </div>
                <input type="hidden" name="technician_id" id="technicianSelect">
                <button type="submit" class="btn-save">Save</button>
            </form>
        </div>
        @endif
    </div>
</div>

<div class="overlay" id="addr-overlay" onclick="handleAddrOverlayClick(event)">
    <div class="sheet">
        <div class="handle"></div>
        <div class="sheet-head">
            <span class="sheet-title">Pilih lokasi pekerjaan</span>
            <button class="close-btn" onclick="closeAddressSheet()">✕</button>
        </div>
        <div class="sbar">
            <input type="text" id="addr-search-input" placeholder="Cari alamat atau tempat..." oninput="onAddrSearch(this.value)">
        </div>
        <div id="addr-sug-list" style="display:none"></div>
        <div id="addr-map-wrap">
            <div id="addr-map"></div>
            <button class="gps-btn" id="addr-gps-btn" onclick="addrUseMyLocation()">📍</button>
        </div>
        <div class="addr-preview" id="addr-preview">Geser peta untuk memilih lokasi...</div>
        <button class="confirm-btn" id="addr-confirm-btn" onclick="confirmAddrLocation()" disabled> Konfirmasi lokasi ini</button>
    </div>
</div>

<script src="{{ asset('js/tasks.js') }}"></script>
@endsection