<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MAVII</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/login.css', 'resources/js/login.js'])
</head>
<body>

    <div class="page-logo">
        <img src="{{ asset('assets/image/logo.png') }}" alt="MAVII Logo">
    </div>

    <div class="login-card">

        <div class="form-box">
            <h2 class="login-title">Login</h2>

            @if($errors->any())
                <div class="error-alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf

                <div class="input-wrapper">
                    <input
                        type="email"
                        name="email"
                        placeholder="Enter Your Email"
                        required
                        autocomplete="email"
                        value="{{ old('email') }}"
                    >
                </div>

                <div class="input-wrapper">
                    <input
                        type="password"
                        id="passwordInput"
                        name="password"
                        placeholder="Enter Your Password"
                        required
                        autocomplete="current-password"
                    >
                    <span class="eye-icon" id="togglePassword">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </span>
                </div>

                <div class="forgot-link">
                    <a href="{{ route('password.request') }}">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-login">Log in</button>
            </form>
        </div>

        <div class="illustration">
            <img src="{{ asset('assets/image/login.png') }}" alt="Login Illustration">
        </div>

    </div>

</body>
</html>