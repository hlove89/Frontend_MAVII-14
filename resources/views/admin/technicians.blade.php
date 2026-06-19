@extends('admin.layout')

@section('content')
@vite(['resources/css/technicians.css', 'resources/js/technicians.js'])

<div class="technicians-page">
    <div class="page-header">
        <h1 class="page-title">Technician Management</h1>
        <p class="page-subtitle">Kelola dan pantau data teknisi dengan mudah</p>
    </div>

    <div class="technicians-grid-2">
        <div class="technicians-list-section">
            <div class="technicians-cards">
                @forelse($technicians as $tech)
                <div class="technician-card" data-id="{{ $tech->id }}">
                    <div class="technician-avatar">
                        @php
                            $imgUrl = '';
                            if(!empty($tech->avatar)) {
                                if(Str::startsWith($tech->avatar, 'http')) {
                                    $imgUrl = $tech->avatar;
                                } else {
                                    $purePath = ltrim($tech->avatar, '/');
                                    $purePath = str_replace('public/', '', $purePath);
                                    $purePath = str_replace('storage/', '', $purePath);
                                    $purePath = ltrim($purePath, '/');
                                    $imgUrl = rtrim(env('VITE_API_BASE_URL'), '/') . '/storage/' . $purePath;
                                }
                            }
                        @endphp
                        @if($imgUrl)
                            <img src="{{ $imgUrl }}" 
                                 alt="{{ $tech->name }}" 
                                 style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
                                 onerror="this.onerror=null; this.parentNode.innerHTML='<i class=\'bi bi-person-circle\'></i>';">
                        @else
                            <i class="bi bi-person-circle"></i>
                        @endif
                    </div>
                    <div class="technician-info-card">
                        <div class="technician-name-card">{{ $tech->name }}</div>
                        <div class="technician-email-card">{{ $tech->email }}</div>
                        <div class="technician-phone-card">{{ $tech->phone ?? 'No phone' }}</div>
                    </div>
                    <div class="technician-actions">
                        <button class="btn-edit" onclick="editTechnician({{ $tech->id }}, '{{ addslashes($tech->name) }}', '{{ addslashes($tech->email) }}', '{{ addslashes($tech->phone) }}')">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-delete" onclick="deleteTechnician({{ $tech->id }}, '{{ addslashes($tech->name) }}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                @empty
                <div class="empty-technicians">
                    <i class="bi bi-people"></i>
                    <p>Belum ada data teknisi</p>
                </div>
                @endforelse
            </div>
        </div>

        <div class="form-section">
            <div class="form-header" id="formHeader">
                <i class="bi bi-person-plus-fill"></i>
                <span id="formTitle">New Technician</span>
            </div>

            <form id="technicianForm" method="POST" action="{{ route('admin.technicians.store') }}" class="technician-form">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" name="id" id="technicianId">
                
                <div class="form-group">
                    <label>Nama</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="Nama lengkap teknisi" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="email@contoh.com" required>
                </div>
                
                <div class="form-group">
                    <label>Nomor Telepon</label>
                    <input type="tel" name="phone" id="phone" class="form-control" placeholder="Nomor telepon" required pattern="[0-9]*" inputmode="numeric" maxlength="15" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>
                
                <div class="form-group" id="passwordGroup">
                    <label>Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="********">
                    <small class="form-text" id="passwordHint">Kosongkan jika tidak mengubah password</small>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="btn-save" id="btnSubmit">
                        <i class="bi bi-save"></i> Add Technician
                    </button>
                    <button type="button" class="btn-cancel" id="btnCancel" style="display: none;" onclick="resetForm()">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>
                </div>
            </form>
        </div>
    </div>


    <div id="confirmDeleteModal" class="confirm-modal">
        <div class="confirm-modal-content">
            <div class="confirm-modal-header">
                <i class="bi bi-exclamation-triangle-fill"></i> Hapus Teknisi
            </div>
            <div class="confirm-modal-body">
                <p>Yakin ingin menghapus teknisi</p>
                <strong id="deleteTargetName"></strong>
                <p style="margin-top:8px;font-size:12px;color:#aaa;">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="confirm-modal-footer">
                <button class="btn-confirm-cancel" id="btnCancelDelete">Batal</button>
                <button class="btn-confirm-delete" id="btnConfirmDelete">
                    <i class="bi bi-trash"></i> Hapus
                </button>
            </div>
        </div>
    </div>

    @if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @foreach ($errors->all() as $error)
                showToast("{{ $error }}", 'error');
            @endforeach
        });
    </script>
    @endif
</div>
@endsection