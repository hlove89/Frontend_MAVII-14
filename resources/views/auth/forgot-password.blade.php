<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - MAVII</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/login.css'])
</head>
<body class="forgot-page">
    <div class="login-card">
        <div class="form-box">
            <h2 class="form-title">Forgot Password</h2>
            <p class="form-desc">Enter your email and we'll send you a link to reset your password</p>

            @if(session('status'))
                <div class="success-alert">
                    <i class="bi bi-check-circle-fill"></i> {{ session('status') }}
                </div>
            @endif
            @if($errors->any())
                <div class="error-alert">
                    <i class="bi bi-exclamation-circle-fill"></i> {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST">
                @csrf
                <div class="input-wrapper">
                    <input type="email" name="email" placeholder="Enter Your Email" required autocomplete="email" value="{{ old('email') }}">
                </div>
                <button type="submit" class="btn-submit">Submit</button>
            </form>
            <div class="back-link">
                <a href="{{ route('login') }}"><i class="bi bi-arrow-left"></i> Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>