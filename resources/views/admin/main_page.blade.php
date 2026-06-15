@extends('admin.layout')

@section('content')
<link rel="stylesheet" href="{{ asset('css/main_page.css') }}">

<div class="main-page">

    <div class="main-page-header">
        <h1>Halaman Utama</h1>
    </div>

    <div class="main-page-actions">

        <div class="action-card" onclick="window.location='{{ route('admin.history') }}'">
            <div class="action-card-icon">
                <i class="bi bi-file-earmark-arrow-down"></i>
            </div>
            <span class="action-card-label">Unduh Laporan</span>
        </div>

        <div class="action-card" onclick="window.location='{{ route('admin.technicians') }}'">
            <div class="action-card-icon">
                <i class="bi bi-person-plus"></i>
            </div>
            <span class="action-card-label">Daftarkan Teknisi Baru</span>
        </div>

    </div>

    <div class="main-page-map-card">
        <div class="map-card-header">
            <i class="bi bi-map"></i>
            <span>Global Map Overview</span>
        </div>
        <div class="map-card-body">
            <div id="globalMap"></div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    window.technicianPoints = @json($technicianPoints ?? []);
</script>
<script src="{{ asset('js/main_page.js') }}"></script>
@endpush