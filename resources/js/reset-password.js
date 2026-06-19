// Fungsi toggle visibilitas password reset
document.querySelectorAll('.toggle-password, .toggle-password-confirm').forEach(icon => {
    icon.addEventListener('click', function() {
        let input = this.previousElementSibling;
        if (input.type === 'password') {
            input.type = 'text';
            this.innerHTML = '<i class="bi bi-eye-slash"></i>';
        } else {
            input.type = 'password';
            this.innerHTML = '<i class="bi bi-eye"></i>';
        }
    });
});