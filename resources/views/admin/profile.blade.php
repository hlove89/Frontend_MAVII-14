@extends('admin.layout')

@section('content')
@vite(['resources/css/profile.css'])

<div class="profile-page">
    <div class="profile-container">
        <div class="profile-header">
            <h1 class="profile-title">Profile</h1>
            <p class="profile-subtitle">Memperbarui data profil secara aman.</p>
        </div>

        @if($errors->any())
        <div class="alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ $errors->first() }}</span>
        </div>
        @endif

        <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data" id="profileForm">
            @csrf @method('PUT')
            <div class="profile-layout">
                <!-- KIRI: FORM UPDATE DATA -->
                <div class="profile-form-wrapper">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="form-group">
                        <label>Peran</label>
                        <input type="text" class="form-control" value="{{ $user->role == 'admin' ? 'Admin' : 'Teknisi' }}" readonly disabled>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                    </div>

                    <div class="form-group">
                        <label>Nomor Telepon</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                    </div>

                    <div class="form-group">
                        <label>Password Baru</label>
                        <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password">
                    </div>

                    <div class="form-group">
                        <label>Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="form-control" placeholder="Konfirmasi password baru">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <i class="bi bi-save"></i> Save Change
                        </button>
                    </div>
                </div>

                <div class="profile-avatar-wrapper">
                    <div class="avatar-container">
                        <div class="avatar-circle" id="avatarPreview">
                        @php
                            $imgUrl = '';
                            if(!empty($user->avatar)) {
                                if(Str::startsWith($user->avatar, 'http')) {
                                    $imgUrl = $user->avatar;
                                } else {
                                    $purePath = ltrim(str_replace('storage/', '', ltrim($user->avatar, '/')), '/');
                                    $imgUrl = rtrim(env('VITE_API_BASE_URL'), '/') . '/storage/' . $purePath;
                                }
                            }
                        @endphp
                            @if($imgUrl)
                                <img src="{{ $imgUrl }}" id="avatarImg" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <i class="bi bi-person-fill" style="display:none; font-size: 60px; color: #ccc;"></i>
                            @else
                                <i class="bi bi-person-fill"></i>
                            @endif
                        </div>
                        
                        <div style="margin-top: 15px;">
                            <input type="file" name="avatar" id="avatarInput" accept="image/*" style="display: none;">
                            <button type="button" class="btn-upload" id="uploadBtn" onclick="document.getElementById('avatarInput').click()">
                                <i class="bi bi-camera-fill"></i> Ganti Foto
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <script>
            document.getElementById('avatarInput').addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const preview = document.getElementById('avatarPreview');
                        preview.innerHTML = `<img src="${event.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
                    };
                    reader.readAsDataURL(e.target.files[0]);
                }
            });

            document.getElementById('profileForm').addEventListener('submit', function(e) {
                const pass = document.getElementById('passwordInput').value;
                if (pass && pass.length < 6) {
                    e.preventDefault();
                    if (typeof showToast === 'function') {
                        showToast('Password minimal 6 karakter', 'error');
                    } else {
                        alert('Password minimal 6 karakter');
                    }
                }
            });
        </script>
    </div>
</div>
@endsection