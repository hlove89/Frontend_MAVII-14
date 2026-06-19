// Siapkan form untuk edit teknisi
window.editTechnician = function(id, name, email, phone) {
    const formHeader = document.getElementById('formHeader');
    if (formHeader) {
        formHeader.innerHTML = `
            <i class="bi bi-pencil-square"></i>
            <span id="formTitle">Edit Technician</span>
        `;
    }
    document.getElementById('formMethod').value = 'PUT';
    const form = document.getElementById('technicianForm');
    form.action = '/admin/technicians/' + id;
    document.getElementById('technicianId').value = id;
    document.getElementById('name').value = name;
    document.getElementById('email').value = email;
    document.getElementById('phone').value = phone || '';
    document.getElementById('password').value = '';
    document.getElementById('password').placeholder = '******** (kosongkan jika tidak diubah)';
    document.getElementById('password').removeAttribute('required');
    document.getElementById('passwordHint').textContent = 'Kosongkan jika tidak mengubah password (min. 6 karakter jika diisi)';
    document.getElementById('btnSubmit').innerHTML = '<i class="bi bi-save"></i> Save Change';
    document.getElementById('btnCancel').style.display = 'flex';
    document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
    const formSection = document.querySelector('.form-section');
    formSection.style.transition = 'all 0.3s';
    formSection.style.boxShadow = '0 0 0 3px #1b1fb8';
    setTimeout(() => { formSection.style.boxShadow = ''; }, 1000);
}

// Reset form ke mode tambah teknisi baru
window.resetForm = function() {
    const formHeader = document.getElementById('formHeader');
    if (formHeader) {
        formHeader.innerHTML = `
            <i class="bi bi-person-plus-fill"></i>
            <span id="formTitle">New Technician</span>
        `;
    }
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('technicianForm').action = '/admin/technicians';
    document.getElementById('technicianId').value = '';
    document.getElementById('name').value = '';
    document.getElementById('email').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('password').value = '';
    document.getElementById('password').placeholder = '********';
    document.getElementById('password').setAttribute('required', 'required');
    document.getElementById('passwordHint').textContent = 'Minimal 6 karakter';
    document.getElementById('btnSubmit').innerHTML = '<i class="bi bi-save"></i> Add Technician';
    document.getElementById('btnCancel').style.display = 'none';
    document.getElementById('password').style.borderColor = '';
}


// Validasi kekuatan/panjang password
function validatePassword() {
    const passwordInput = document.getElementById('password');
    const isEditMode    = document.getElementById('formMethod').value === 'PUT';
    const val           = passwordInput.value;
    if (isEditMode && val === '') return true;
    if (val.length < 6) {
        passwordInput.style.borderColor = '#ef4444';
        passwordInput.style.boxShadow   = '0 0 0 3px rgba(239,68,68,0.15)';
        setTimeout(() => {
            passwordInput.style.borderColor = '';
            passwordInput.style.boxShadow   = '';
        }, 3000);
        showToast('Password minimal 6 karakter!', 'error');
        passwordInput.focus();
        return false;
    }
    return true;
}

let deleteTargetId = null;

// Tampilkan modal konfirmasi hapus
window.deleteTechnician = function(id, name) {
    deleteTargetId = String(id);
    document.getElementById('deleteTargetName').textContent = name;
    document.getElementById('confirmDeleteModal').classList.add('active');
}

// Tutup modal konfirmasi hapus
function closeConfirmModal() {
    document.getElementById('confirmDeleteModal').classList.remove('active');
    // JANGAN reset deleteTargetId di sini
}

// Hapus elemen teknisi dari tampilan UI
function removeCardFromDOM(id) {
    let card = null;
    document.querySelectorAll('.technician-card').forEach(c => {
        if (String(c.dataset.id) === String(id)) card = c;
    });
    if (card) {
        card.style.transition = 'all 0.3s ease';
        card.style.opacity    = '0';
        card.style.transform  = 'translateX(30px)';
        setTimeout(() => {
            card.remove();
            const remaining = document.querySelectorAll('.technician-card');
            if (remaining.length === 0) {
                const container = document.querySelector('.technicians-cards');
                if (container) {
                    container.innerHTML = `
                        <div class="empty-technicians">
                            <i class="bi bi-people"></i>
                            <p>Belum ada data teknisi</p>
                        </div>`;
                }
            }
        }, 300);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('passwordHint').textContent = 'Minimal 6 karakter';
    document.getElementById('password').setAttribute('required', 'required');


    document.getElementById('technicianForm').addEventListener('submit', function(e) {
        if (!validatePassword()) e.preventDefault();
    });

    document.getElementById('password').addEventListener('input', function() {
        this.style.borderColor = '';
        this.style.boxShadow   = '';
    });

    document.getElementById('btnConfirmDelete').addEventListener('click', function () {
        if (!deleteTargetId) return;

        const idToDelete = deleteTargetId;
        closeConfirmModal();
        removeCardFromDOM(idToDelete);
        deleteTargetId = null;

        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            showToast('CSRF token tidak ditemukan', 'error');
            return;
        }

        fetch(`/admin/technicians/${idToDelete}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken.content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        })
        .then(async res => {
            const contentType = res.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                return res.json();
            } else {
                // If not JSON, likely a redirect to login or error page
                if (res.status === 401 || res.status === 419) {
                    location.reload();
                    return;
                }
                throw new Error("Invalid response from server");
            }
        })
        .then(data => {
            if (!data) return;
            if (data.success) {
                showToast('Teknisi berhasil dihapus!', 'success');
            } else {
                showToast(data.message || 'Gagal menghapus teknisi', 'error');
                setTimeout(() => location.reload(), 1500);
            }
        })
        .catch(err => {
            console.error('Delete error:', err);
            showToast('Terjadi kesalahan, coba lagi', 'error');
            setTimeout(() => location.reload(), 1500);
        });
    });

    document.getElementById('btnCancelDelete').addEventListener('click', function() {
        closeConfirmModal();
        deleteTargetId = null;
    });

    document.getElementById('confirmDeleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeConfirmModal();
            deleteTargetId = null;
        }
    });
});
