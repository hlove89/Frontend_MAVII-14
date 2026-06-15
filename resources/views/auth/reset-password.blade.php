<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - MAVII</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/login.css'])
</head>
<body class="reset-page">
    <div class="page-logo">
        <img src="{{ asset('assets/image/logo.png') }}" alt="MAVII Logo">
    </div>
    <div class="login-card">
        <div class="form-box">
            <h2 class="form-title">Reset Password</h2>

            @if($errors->any())
                <div class="error-alert">
                    <i class="bi bi-exclamation-circle-fill"></i> {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('password.update') }}" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="input-wrapper">
                    <input type="email" name="email" placeholder="Enter Your Email" value="{{ $email ?? old('email') }}" required readonly>
                </div>

                <div class="input-wrapper">
                    <input type="password" name="password" id="password" placeholder="New Password" required autocomplete="new-password">
                    <span class="eye-icon toggle-password">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>

                <div class="input-wrapper">
                    <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Confirm Password" required>
                    <span class="eye-icon toggle-password-confirm">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>

                <button type="submit" class="btn-submit">Reset Password</button>
            </form>

            <div class="back-link">
                <a href="{{ route('login') }}"><i class="bi bi-arrow-left"></i> Back to Login</a>
            </div>
        </div>
    </div>

    @vite(['resources/js/reset-password.js'])
</body>
</html>