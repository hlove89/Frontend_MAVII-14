<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - MAVII</title>
    @vite(['resources/css/welcome.css'])
</head>
<body>

    <div class="page-logo">
        <img src="{{ asset('assets/image/logo.png') }}" alt="MAVII Logo">
    </div>

    <div class="welcome-card">
        <div class="text-box">
            <h1>Let's Get Started</h1>
            <p>Manajemen Asisten Visual Infrastruktur Internet</p>
            <a href="{{ route('login') }}" class="btn-started">Get Started</a>
        </div>

        <div class="illustration">
            <img src="{{ asset('assets/image/plash.png') }}" alt="Welcome Illustration">
        </div>
    </div>

</body>
</html>