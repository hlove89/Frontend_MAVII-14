(function () {
    const bellBtn    = document.getElementById('bellBtn');
    let dropdown     = document.getElementById('notifDropdown');
    const badge      = document.getElementById('notifBadge');
    const notifList  = document.getElementById('notifList');
    const markAllBtn = document.getElementById('markAllRead');
    const clearAllBtn = document.getElementById('clearAllNotif');
    const csrfToken  = document.querySelector('meta[name="csrf-token"]').content;

    if (dropdown.parentElement !== document.body) {
        document.body.appendChild(dropdown);
    }
    dropdown.style.position = 'fixed';
    dropdown.style.top = 'auto';
    dropdown.style.right = 'auto';
    dropdown.style.bottom = 'auto';
    dropdown.style.left = 'auto';
    dropdown.style.zIndex = '9999999';

    function updatePosition() {
        const rect = bellBtn.getBoundingClientRect();
        dropdown.style.top = (rect.bottom + 10) + 'px';
        dropdown.style.right = (window.innerWidth - rect.right) + 'px';
    }

    bellBtn.addEventListener('click', e => {
        e.stopPropagation();
        dropdown.classList.toggle('show');
        if (dropdown.classList.contains('show')) {
            updatePosition();
            loadNotifications();
            window.addEventListener('scroll', updatePosition);
            window.addEventListener('resize', updatePosition);
        } else {
            window.removeEventListener('scroll', updatePosition);
            window.removeEventListener('resize', updatePosition);
        }
    });

    document.addEventListener('click', () => {
        dropdown.classList.remove('show');
        window.removeEventListener('scroll', updatePosition);
        window.removeEventListener('resize', updatePosition);
    });
    dropdown.addEventListener('click', e => e.stopPropagation());

    markAllBtn.addEventListener('click', e => {
        e.preventDefault();
        fetch('/admin/notifications/read-all', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }).then(() => loadNotifications());
    });

    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', e => {
            e.preventDefault();
            fetch('/admin/notifications/clear-all', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            })
            .then(() => {
                loadNotifications();
                showToast('Semua notifikasi berhasil dihapus', 'success');
            })
            .catch(() => {
                showToast('Gagal menghapus notifikasi', 'error');
            });
        });
    }

    function showToast(message, type) {
        const existing = document.querySelector('.notif-custom-toast');
        if (existing) existing.remove();
        const toast = document.createElement('div');
        toast.className = `notif-custom-toast ${type === 'success' ? 'success' : 'error'}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    function loadNotifications() {
        fetch('/admin/notifications')
            .then(r => r.json())
            .then(data => {
                updateBadge(data.unread_count);
                renderNotifications(data.notifications);
            })
            .catch(() => {
                notifList.innerHTML = '<div class="notif-empty">Gagal memuat notifikasi.</div>';
            });
    }

    function updateBadge(count) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    }

    function renderNotifications(items) {
        if (!items || items.length === 0) {
            notifList.innerHTML = '<div class="notif-empty">Tidak ada notifikasi.</div>';
            return;
        }

        notifList.innerHTML = items.map(n => {
            const isTaskAssigned = n.message.startsWith('Anda mendapat tugas baru');

            const iconClass = n.status === 'accepted' ? 'accept'
                            : n.status === 'rejected' ? 'reject'
                            : 'pending';

            const iconBi = n.status === 'accepted' ? 'bi-check-lg'
                         : n.status === 'rejected' ? 'bi-x-lg'
                         : 'bi-clock';

            let actionHtml = '';

            if (isTaskAssigned) {
                if (n.status === 'accepted') {
                    actionHtml = `<span class="notif-status-badge accepted">
                        <i class="bi bi-check-lg"></i> Tugas diterima
                    </span>`;
                } else if (n.status === 'rejected') {
                    actionHtml = `<span class="notif-status-badge rejected">
                        <i class="bi bi-x-lg"></i> Tugas ditolak
                    </span>`;
                } else {
                    actionHtml = `<span class="notif-status-badge pending">
                        <i class="bi bi-clock"></i> Menunggu respons teknisi
                    </span>`;
                }
            } else {
                if (n.status === 'accepted') {
                    actionHtml = `<span class="notif-status-badge accepted">
                        <i class="bi bi-check-lg"></i> Tugas diterima
                    </span>`;
                } else if (n.status === 'rejected') {
                    actionHtml = `<span class="notif-status-badge rejected">
                        <i class="bi bi-x-lg"></i> Tugas ditolak
                    </span>`;
                }
            }

            return `
                <div class="notif-item ${n.unread ? 'unread' : ''}" data-id="${n.id}">
                    <div class="notif-icon ${iconClass}">
                        <i class="bi ${iconBi}"></i>
                    </div>
                    <div class="notif-body">
                        <p class="notif-title">${escHtml(n.technician_name)}</p>
                        <p class="notif-desc">${escHtml(n.message)}</p>
                        <span class="notif-time">${escHtml(n.time_ago)}</span>
                        ${actionHtml}
                    </div>
                </div>`;
        }).join('');
    }

    function escHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    loadNotifications();
    setInterval(loadNotifications, 30000);
})();