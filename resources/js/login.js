const toggle = document.getElementById('togglePassword');
const input  = document.getElementById('passwordInput');
const icon   = document.getElementById('eyeIcon');

// Toggle visibilitas password
if (toggle && input && icon) {
    toggle.addEventListener('click', function () {
        const isPassword = input.type === 'password';
        input.type       = isPassword ? 'text' : 'password';
        icon.className   = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
}