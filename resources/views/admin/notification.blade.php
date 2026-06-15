<div class="bell-wrapper" id="bellWrapper">
    <button class="bell-icon-btn" id="bellBtn" aria-label="Notifikasi">
        <i class="bi bi-bell"></i>
        <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
    </button>

    <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-dropdown-header">
            <span>Notifikasi</span>
            <div class="notif-actions-icon">
                <a href="#" id="markAllRead" title="Tandai semua dibaca">
                    <i class="bi bi-check2-all"></i>
                </a>
                <a href="#" id="clearAllNotif" title="Hapus semua notifikasi" style="margin-left: 8px;">
                    <i class="bi bi-trash"></i>
                </a>
            </div>
        </div>
        <div class="notif-list" id="notifList">
            <div class="notif-empty">Memuat notifikasi...</div>
        </div>
    </div>
</div>