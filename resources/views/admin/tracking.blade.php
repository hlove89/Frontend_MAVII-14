@extends('admin.layout')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/tracking.css') }}?v={{ time() }}">
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<div class="tracking-page">
    <div class="page-header">
        <h1 class="page-title">Tracking Monitoring</h1>
        <p class="page-subtitle">Pantau teknisi lebih jelas</p>
    </div>

    <div class="panels-grid" id="technicianContainer">
        <div class="loading-state" style="text-align: center; padding: 50px; width: 100%;">
            <p>Memuat data teknisi...</p>
        </div>
    </div>

    <div id="detailModal" class="detail-modal">
        <div class="detail-modal-content">
            <div class="modal-header">
                <span><i class="bi bi-info-circle-fill"></i> Detail Pekerjaan</span>
                <span class="close" onclick="closeDetailModal()">&times;</span>
            </div>
            <div class="modal-body" id="detailModalBody">
                <div class="loading-placeholder">
                    <i class="bi bi-hand-index-thumb"></i>
                    <p>Memuat data...</p>
                </div>
            </div>
        </div>
    </div>

    <div id="fullMapModal" class="full-map-modal">
        <div class="full-map-content">
            <div class="full-map-header">
                <h3 id="fullMapTitle">Lokasi Teknisi</h3>
                <span class="close-map" onclick="closeFullMapModal()">&times;</span>
            </div>
            <div id="fullMapBody"></div>
        </div>
    </div>
</div>
<script src="{{ asset('js/tracking.js') }}?v={{ time() }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initTracking({
            API_BASE_URL: "{{ env('VITE_API_BASE_URL') }}",
            ACCESS_TOKEN: "{{ session('access_token') }}"
        });
    });
</script>
@endsection