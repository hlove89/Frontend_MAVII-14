document.addEventListener('DOMContentLoaded', function() {
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('avatarPreview');
                    if(preview) {
                        preview.innerHTML = `<img src="${event.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
                    }
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    }

    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            const passInput = document.getElementById('passwordInput');
            const pass = passInput ? passInput.value : '';
            
            const nameInput = document.getElementById('nameInput');
            const nameVal = nameInput ? nameInput.value.trim() : '';

            if (!nameVal) {
                e.preventDefault();
                if (typeof window.showToast === 'function') {
                    window.showToast('Nama tidak boleh kosong', 'error');
                } else {
                    alert('Nama tidak boleh kosong');
                }
                return;
            }
            if (nameVal.length > 50) {
                e.preventDefault();
                if (typeof window.showToast === 'function') {
                    window.showToast('Nama maksimal 50 karakter', 'error');
                } else {
                    alert('Nama maksimal 50 karakter');
                }
                return;
            }
            if (pass && pass.length < 6) {
                e.preventDefault();
                if (typeof window.showToast === 'function') {
                    window.showToast('Password minimal 6 karakter', 'error');
                } else {
                    alert('Password minimal 6 karakter');
                }
            }
        });
    }
});

window.updateNameCounter = function() {
    const input = document.getElementById('nameInput');
    const counter = document.getElementById('nameCounter');
    if(input && counter) {
        const len = input.value.length;
        counter.textContent = len + '/50';
        counter.style.color = len >= 50 ? '#e53e3e' : '#888';
    }
}