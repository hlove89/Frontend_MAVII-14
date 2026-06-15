function previewAndSubmit(file, avatarPreview, form) {
    if (!file) return;
    
    if (!file.type.startsWith('image/')) {
        alert('File harus berupa gambar!');
        return false;
    }
    if (file.size > 5 * 1024 * 1024) {
        alert('Ukuran file maksimal 5MB!');
        return false;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        avatarPreview.innerHTML = '';
        const img = document.createElement('img');
        img.src = e.target.result;
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'cover';
        avatarPreview.appendChild(img);
    };
    reader.readAsDataURL(file);
    
    return true;
}

function showToast(message, type = 'success') {
    const old = document.getElementById('toastNotif');
    if (old) old.remove();

    const toast = document.createElement('div');
    toast.id = 'toastNotif';
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle-fill' : type === 'error' ? 'x-circle-fill' : 'info-circle-fill'}"></i>
        ${message}
    `;
    document.body.appendChild(toast);

    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

document.addEventListener('DOMContentLoaded', function() {

    const profileForm = document.querySelector('.profile-form');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            const passwordInput = profileForm.querySelector('input[name="password"]');
            const password = passwordInput ? passwordInput.value : '';

            if (password.length > 0 && password.length < 6) {
                e.preventDefault();
                showToast('Password minimal harus 6 karakter!', 'error');
                passwordInput.focus();
                return false;
            }
        });
    }
    
    const uploadBtn = document.getElementById('uploadBtn');
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    const avatarForm = document.getElementById('avatarForm');
    const successMsg = document.querySelector('meta[name="flash-success"]');
    const errorMsg   = document.querySelector('meta[name="flash-error"]');
    if (successMsg) showToast(successMsg.content, 'success');
    if (errorMsg)   showToast(errorMsg.content, 'error');
    
    if (uploadBtn && avatarInput) {
        uploadBtn.addEventListener('click', function() {
            avatarInput.click();
        });
    }
    
    if (avatarInput && avatarForm) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const isValid = previewAndSubmit(file, avatarPreview, avatarForm);
                if (isValid) {
                    avatarForm.submit();
                }
            }
        });
    }
});