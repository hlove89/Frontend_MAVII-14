let currentTaskId = null;
let currentFilter = 'all';
let currentTaskData = null;

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message, type = 'info') {
    const existing = document.querySelector('.dp-toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `dp-toast${type === 'warning' ? ' dp-toast-warning' : type === 'error' ? ' dp-toast-error' : ''}`;
    toast.innerHTML = `<i class="bi ${type === 'warning' ? 'bi-exclamation-circle-fill' : type === 'error' ? 'bi-x-circle-fill' : 'bi-info-circle-fill'}"></i> ${message}`;
    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        requestAnimationFrame(() => toast.classList.add('show'));
    });

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Fungsi pencarian riwayat
window.searchHistory = function (query) {
    const q = query.toLowerCase().trim();
    document.querySelectorAll('.history-card').forEach(function (card) {
        const searchData = (card.dataset.search || '').toLowerCase();
        const status = card.dataset.status;
        let passFilter = (currentFilter === 'all') ||
            (currentFilter === 'finished' && status === 'completed') ||
            (currentFilter === 'canceled' && (status === 'canceled' || status === 'rejected'));
        const passSearch = q === '' || searchData.includes(q);
        card.style.display = (passFilter && passSearch) ? 'flex' : 'none';
    });
}

// Filter riwayat berdasarkan status
window.filterHistory = function (type, element) {
    currentFilter = type;
    const q = (document.getElementById('historySearchInput')?.value || '').toLowerCase().trim();
    document.querySelectorAll('.history-card').forEach(function (card) {
        const status = card.dataset.status;
        const searchData = (card.dataset.search || '').toLowerCase();
        let passFilter = (type === 'all') ||
            (type === 'finished' && status === 'completed') ||
            (type === 'canceled' && (status === 'canceled' || status === 'rejected'));
        const passSearch = q === '' || searchData.includes(q);
        card.style.display = (passFilter && passSearch) ? 'flex' : 'none';
    });
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    element.classList.add('active');
}

// Tampilkan panel detail pekerjaan
window.showJobDetails = function (taskId) {
    currentTaskId = taskId;
    currentTaskData = null;

    document.querySelectorAll('.history-card').forEach(card => card.classList.remove('selected'));
    const clickedCard = document.querySelector(`.history-card[data-id="${taskId}"]`);
    if (clickedCard) clickedCard.classList.add('selected');

    const panel = document.getElementById('jobDetailsPanel');
    panel.classList.add('active');
    document.querySelector('.history-container')?.classList.add('panel-open');

    const summaryEl = document.getElementById('panelSummary');
    if (summaryEl) summaryEl.style.display = 'none';

    document.getElementById('jobDetailsContent').innerHTML = `
        <div class="empty-detail">
            <i class="bi bi-hourglass-split"></i>
            <p>Memuat data...</p>
        </div>
    `;

    fetch(`/admin/history/detail/${taskId}`)
        .then(response => response.json())
        .then(data => {
            currentTaskData = data;
            renderJobDetails(data);
        })
        .catch(() => {
            document.getElementById('jobDetailsContent').innerHTML = `
                <div class="empty-detail">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <p>Gagal memuat data</p>
                </div>
            `;
        });
}

function renderJobDetails(data) {
    let actions = data.actions;
    if (typeof actions === 'string') {
        try {
            let parsed = JSON.parse(actions);
            if (Array.isArray(parsed)) {
                actions = parsed;
            } else {
                actions = [actions];
            }
        } catch (e) {
            actions = [actions];
        }
    } else if (!Array.isArray(actions)) {
        actions = [];
    }

    const isCanceled = data.status === 'rejected' || data.status === 'canceled';
    const statusBadgeClass = isCanceled ? 'badge-canceled' : 'badge-finished';
    const statusText = isCanceled ? 'Canceled' : 'Finished';

    const summaryEl = document.getElementById('panelSummary');
    if (summaryEl) {
        summaryEl.style.display = 'flex';
        summaryEl.innerHTML = `
            <div class="panel-summary-avatar"><i class="bi bi-tools"></i></div>
            <div class="panel-summary-info">
                <div class="panel-summary-id">TK-${String(data.id).padStart(4, '0')}</div>
                <div class="panel-summary-title">${escapeHtml(data.title)}</div>
                <div class="panel-summary-date">${escapeHtml(data.created_at || '-')}</div>
            </div>
            <span class="${statusBadgeClass}" style="padding:4px 10px;border-radius:30px;font-size:10px;font-weight:700;text-transform:uppercase;flex-shrink:0;">${statusText}</span>
        `;
    }

    let actionsText = '';
    if (actions && actions.length > 0) {
        actionsText = actions.join(', ');
    } else {
        actionsText = 'Tidak ada tindakan';
    }

    const photosHtml = (data.photos && data.photos.length > 0)
        ? data.photos.map(p => `<div style="display:flex;flex-direction:column;align-items:center;gap:4px;"><img src="${p.url}" class="bukti-foto-img" style="cursor:pointer;"><span style="font-size:10px;color:#888;text-align:center;">${escapeHtml(p.note)}</span></div>`).join('')
        : '<p style="font-size:12px;color:#bbb;margin:0;">Tidak ada foto</p>';

    document.getElementById('jobDetailsContent').innerHTML = `
        <div class="detail-section">
            <h4><i class="bi bi-person-badge"></i> Informasi Pelanggan</h4>
            <div class="info-row">
                <div class="info-icon"><i class="bi bi-building"></i></div>
                <div class="info-content"><div class="info-label">Nama</div><div class="info-value">${escapeHtml(data.customer_name || '-')}</div></div>
            </div>
            <div class="info-row">
                <div class="info-icon"><i class="bi bi-telephone-fill"></i></div>
                <div class="info-content"><div class="info-label">Telepon</div><div class="info-value">${escapeHtml(data.customer_phone || '-')}</div></div>
            </div>
            <div class="info-row">
                <div class="info-icon"><i class="bi bi-geo-alt-fill"></i></div>
                <div class="info-content"><div class="info-label">Lokasi</div><div class="info-value">${escapeHtml(data.address || '-')}</div></div>
            </div>
        </div>
        <div class="detail-section">
            <h4><i class="bi bi-tools"></i> Detail Pekerjaan</h4>
            <table class="detail-table">
                <tr><td>ID Tugas</td><td>TK-${String(data.id).padStart(4, '0')}</td></tr>
                <tr><td>Jenis Gangguan</td><td>${escapeHtml(data.title)}</td></tr>
            </table>
        </div>
        <div class="detail-section">
            <h4><i class="bi bi-person-workspace"></i> Teknisi</h4>
            <table class="detail-table">
                <tr><td>Nama</td><td>${escapeHtml(data.technician_name || '-')}</td></tr>
                <tr><td>Telepon</td><td>${escapeHtml(data.technician_phone || '-')}</td></tr>
                <tr><td>Email</td><td>${escapeHtml(data.technician_email || '-')}</td></tr>
            </table>
        </div>
        <div class="detail-section">
            <h4><i class="bi bi-list-check"></i> Hasil dan Status</h4>
            <div class="bukti-foto-row">
                <div class="info-label" style="font-size:11px;color:#999;margin-bottom:6px;">Bukti Foto</div>
                <div class="bukti-foto-grid">${photosHtml}</div>
            </div>
            <table class="detail-table" style="margin-top:12px;">
                <tr><td>Tindakan</td><td>${escapeHtml(actionsText)}</td></tr>
                <tr><td>Catatan</td><td>${escapeHtml(data.catatan || 'Tidak ada catatan')}</td></tr>
            </table>
            <div class="status-selesai-row ${isCanceled ? 'status-canceled' : 'status-finished'}">
                <div class="status-icon">
                    <i class="bi ${isCanceled ? 'bi-x-circle-fill' : 'bi-check-circle-fill'}"></i>
                </div>
                <div>
                    <div class="status-label">${isCanceled ? 'Canceled' : 'Finished'}</div>
                    <div class="status-desc">Pekerjaan telah ${isCanceled ? 'canceled' : 'finished'}</div>
                    <div class="status-date">${escapeHtml(data.completed_at || '-')}</div>
                </div>
            </div>
        </div>
        <button class="share-report-btn" onclick="openShareModal()">
            <i class="bi bi-share-fill"></i> Share Report
        </button>
    `;
}

window.closeJobDetails = function () {
    document.getElementById('jobDetailsPanel').classList.remove('active');
    document.querySelector('.history-container')?.classList.remove('panel-open');
    document.querySelectorAll('.history-card').forEach(c => c.classList.remove('selected'));
    currentTaskId = null;
    currentTaskData = null;
}

window.toggleDownloadDropdown = function () {
    document.getElementById('downloadDropdown').classList.toggle('show');
}

document.addEventListener('click', function (e) {
    const dropdown = document.getElementById('downloadDropdown');
    const btn = document.querySelector('.download-btn');
    if (dropdown && btn && !btn.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

function getAllCardsData() {
    return Array.from(document.querySelectorAll('.history-card')).map(card => ({
        id: card.querySelector('.card-id')?.textContent?.trim() || '',
        title: card.querySelector('.card-title')?.textContent?.trim() || '',
        cust: card.querySelector('.card-customer')?.textContent?.trim() || '',
        loc: card.querySelector('.card-location')?.textContent?.replace(/\s+/g, ' ').trim() || '',
        date: card.querySelector('.card-date')?.textContent?.replace(/\s+/g, ' ').trim() || '',
        tech: card.querySelector('.card-tech')?.textContent?.replace(/\s+/g, ' ').trim() || '',
        badge: card.querySelector('.card-badge')?.textContent?.trim() || '',
    }));
}

function buildCsv(rows) {
    const headers = ['ID Tugas', 'Jenis Gangguan', 'Nama Pelanggan', 'Lokasi', 'Tanggal Selesai', 'Teknisi', 'Status'];
    const escape = v => `"${String(v).replace(/"/g, '""')}"`;
    return [
        headers.map(escape).join(','),
        ...rows.map(r => [r.id, r.title, r.cust, r.loc, r.date, r.tech, r.badge].map(escape).join(','))
    ].join('\r\n');
}

function downloadCsvBlob(csvContent, filename) {
    const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = Object.assign(document.createElement('a'), { href: url, download: filename });
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// Download laporan (CSV atau PDF)
window.downloadReport = function (type) {
    const allRows = getAllCardsData();
    if (type === 'csv') {
        downloadCsvBlob(buildCsv(allRows), 'history-semua.csv');
        document.getElementById('downloadDropdown').classList.remove('show');
    } else if (type === 'monthly') {
        document.getElementById('downloadDropdown').classList.remove('show');
        openDatePicker('monthly');
    } else if (type === 'date') {
        document.getElementById('downloadDropdown').classList.remove('show');
        openDatePicker('date');
    }
}

let dpMode = 'date';
let dpYear = new Date().getFullYear();
let dpMonth = new Date().getMonth();
let dpDay = null;
let dpSelMonth = null;

// Buka modal datepicker
function openDatePicker(mode) {
    dpMode = mode;
    dpYear = new Date().getFullYear();
    dpMonth = new Date().getMonth();
    dpDay = null;
    dpSelMonth = null;

    const confirmBtn = document.getElementById('dpConfirmBtn');
    confirmBtn.disabled = true;

    const calGrid = document.getElementById('dpCalendarGrid');
    const monthGrid = document.getElementById('dpMonthGrid');

    if (mode === 'monthly') {
        document.getElementById('datePickerTitle').innerHTML = '<i class="bi bi-calendar-month"></i> Pilih Bulan';
        calGrid.style.display = 'none';
        monthGrid.style.display = 'block';
        dpRenderMonthNav();
        dpRenderMonthGrid();
    } else {
        document.getElementById('datePickerTitle').innerHTML = '<i class="bi bi-calendar-date"></i> Pilih Tanggal';
        calGrid.style.display = 'block';
        monthGrid.style.display = 'none';
        dpRenderCalendar();
    }

    document.getElementById('datePickerModal').classList.add('active');
}

window.closeDatePicker = function () {
    document.getElementById('datePickerModal').classList.remove('active');
}

function dpRenderMonthNav() {
    document.getElementById('dpMonthLabel').textContent = dpYear;
}

function dpRenderCalendar() {
    const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    document.getElementById('dpMonthLabel').textContent = `${monthNames[dpMonth]} ${dpYear}`;

    const today = new Date();
    const firstDay = new Date(dpYear, dpMonth, 1).getDay();
    const daysInMonth = new Date(dpYear, dpMonth + 1, 0).getDate();
    const grid = document.getElementById('dpDaysGrid');
    grid.innerHTML = '';

    for (let i = 0; i < firstDay; i++) {
        const empty = document.createElement('button');
        empty.className = 'dp-empty';
        empty.disabled = true;
        grid.appendChild(empty);
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const btn = document.createElement('button');
        btn.textContent = d;
        if (d === today.getDate() && dpMonth === today.getMonth() && dpYear === today.getFullYear())
            btn.classList.add('dp-today');
        if (dpDay === d) btn.classList.add('dp-selected');
        btn.onclick = () => dpSelectDay(d);
        grid.appendChild(btn);
    }
}

function dpRenderMonthGrid() {
    document.querySelectorAll('.dp-month-names button').forEach((btn, i) => {
        btn.classList.toggle('dp-month-selected', i === dpSelMonth);
    });
}

window.dpChangeMonth = function (dir) {
    if (dpMode === 'monthly') {
        dpYear += dir;
        dpRenderMonthNav();
        dpRenderMonthGrid();
    } else {
        dpMonth += dir;
        if (dpMonth > 11) { dpMonth = 0; dpYear++; }
        if (dpMonth < 0) { dpMonth = 11; dpYear--; }
        dpDay = null;
        document.getElementById('dpConfirmBtn').disabled = true;
        dpRenderCalendar();
    }
}

function dpSelectDay(d) {
    dpDay = d;
    dpRenderCalendar();
    document.getElementById('dpConfirmBtn').disabled = false;
}

window.dpSelectMonth = function (m) {
    dpSelMonth = m;
    dpRenderMonthGrid();
    document.getElementById('dpConfirmBtn').disabled = false;
}

window.dpConfirm = function () {
    const allRows = getAllCardsData();
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    if (dpMode === 'monthly') {
        const filtered = allRows.filter(r => r.date.includes(monthNames[dpSelMonth]) && r.date.includes(String(dpYear)));
        if (!filtered.length) { showToast(`Tidak ada data untuk ${monthNames[dpSelMonth]} ${dpYear}`, 'warning'); return; }
        downloadCsvBlob(buildCsv(filtered), `history-${dpYear}-${String(dpSelMonth + 1).padStart(2, '0')}.csv`);
    } else {
        const dayStr = String(dpDay).padStart(2, '0');
        const filtered = allRows.filter(r =>
            r.date.includes(dayStr) && r.date.includes(monthNames[dpMonth]) && r.date.includes(String(dpYear))
        );
        if (!filtered.length) { showToast(`Tidak ada data untuk ${dayStr} ${monthNames[dpMonth]} ${dpYear}`, 'warning'); return; }
        downloadCsvBlob(buildCsv(filtered), `history-${dpYear}-${String(dpMonth + 1).padStart(2, '0')}-${dayStr}.csv`);
    }
    closeDatePicker();
}

function buildPdfHtml(data) {
    let actions = data.actions;
    if (typeof actions === 'string') {
        try {
            let parsed = JSON.parse(actions);
            if (Array.isArray(parsed)) actions = parsed;
            else actions = [actions];
        } catch (e) { actions = [actions]; }
    } else if (!Array.isArray(actions)) actions = [];

    const isCanceled = data.status === 'canceled' || data.status === 'rejected';
    const statusLabel = isCanceled ? 'Canceled' : 'Finished';
    const statusColor = isCanceled ? '#dc3545' : '#28a745';
    const statusBg = isCanceled ? '#f8d7da' : '#d4edda';
    const statusTxt = isCanceled ? '#721c24' : '#155724';
    const taskCode = `TK-${String(data.id).padStart(4, '0')}`;

    const actionsText = (actions.length > 0) ? actions.join(', ') : 'Tidak ada tindakan';
    const dateStr = new Date().toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });

    const getFullUrl = (url) => {
        if (!url) return '';
        if (url.startsWith('http')) return url;
        return window.location.origin + (url.startsWith('/') ? url : '/' + url);
    };

    const photosHtml = (data.photos && data.photos.length > 0)
        ? data.photos.map(p => {
            const imgUrl = p.base64 || getFullUrl(p.url);
            return `
                <div style="display: inline-block; text-align: center; margin-right: 15px; margin-bottom: 5px; vertical-align: top; width: 180px;">
                    <img src="${imgUrl}" style="width: 180px; height: 110px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="font-size: 10px; color: #555; margin-top: 4px; text-align: center; width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${escapeHtml(p.note)}">${escapeHtml(p.note)}</div>
                </div>
            `;
        }).join('')
        : '<p style="color: #aaa; font-size: 12px;">Tidak ada foto</p>';

    const pdfStyles = `
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            background: #fff;
            padding: 20px 25px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            display: flex;
            flex-direction: column;
            height: 1083px;
        }
        .pdf-header { margin-bottom: 10px; }
        .pdf-header h1 { font-size: 18px; font-weight: 700; color: #1b1fb8; margin-bottom: 2px; }
        .pdf-header p  { font-size: 10px; color: #666; }
        .status-badge { display: inline-block; align-self: flex-start; padding: 2px 10px; border-radius: 20px; font-size: 9px; font-weight: 700; text-transform: uppercase; background: ${statusBg}; color: ${statusTxt}; margin-bottom: 10px; }
        .section-title { font-size: 11px; font-weight: 700; color: #1b1fb8; border-bottom: 1.5px solid #1b1fb8; padding-bottom: 2px; margin: 10px 0 6px; text-transform: uppercase; letter-spacing: 0.3px; }
        .info-row { display: flex; margin-bottom: 4px; padding-bottom: 4px; border-bottom: 1px solid #f0f0f0; }
        .info-label { width: 120px; color: #666; font-weight: 500; flex-shrink: 0; }
        .info-value { flex: 1; color: #111; word-break: break-word; }
        .photos-row { display: flex; flex-direction: row; flex-wrap: wrap; gap: 10px; margin-top: 2px; }
        .pdf-footer { margin-top: auto; padding-top: 8px; border-top: 1px solid #e0e0e0; text-align: center; font-size: 9px; color: #999; }
        @media print { body { padding: 10px 15px; } }
    `;

    const pdfBody = `
        <div class="pdf-header">
            <h1>Laporan Pekerjaan</h1>
            <p>MAVII - Manajemen Asisten Visual Infrastruktur Internet</p>
        </div>
        <span class="status-badge">${statusLabel}</span>

        <div class="section-title">Informasi Pelanggan</div>
        <div class="info-row"><div class="info-label">Nama</div><div class="info-value">${escapeHtml(data.customer_name || '-')}</div></div>
        <div class="info-row"><div class="info-label">Telepon</div><div class="info-value">${escapeHtml(data.customer_phone || '-')}</div></div>
        <div class="info-row"><div class="info-label">Lokasi</div><div class="info-value">${escapeHtml(data.address || '-')}</div></div>
        <div class="info-row"><div class="info-label">Tanggal</div><div class="info-value">${escapeHtml(data.completed_at || '-')}</div></div>

        <div class="section-title">Detail Pekerjaan</div>
        <div class="info-row"><div class="info-label">ID Tugas</div><div class="info-value">${taskCode}</div></div>
        <div class="info-row"><div class="info-label">Jenis Gangguan</div><div class="info-value">${escapeHtml(data.title || '-')}</div></div>

        <div class="section-title">Teknisi</div>
        <div class="info-row"><div class="info-label">Nama</div><div class="info-value">${escapeHtml(data.technician_name || '-')}</div></div>
        <div class="info-row"><div class="info-label">Telepon</div><div class="info-value">${escapeHtml(data.technician_phone || '-')}</div></div>
        <div class="info-row"><div class="info-label">Email</div><div class="info-value">${escapeHtml(data.technician_email || '-')}</div></div>

        <div class="section-title">Hasil dan Status</div>
        <div class="info-row"><div class="info-label">Bukti Foto</div><div class="info-value"><div class="photos-row">${photosHtml}</div></div></div>
        <div class="info-row"><div class="info-label">Tindakan</div><div class="info-value">${escapeHtml(actionsText)}</div></div>
        <div class="info-row"><div class="info-label">Catatan</div><div class="info-value">${escapeHtml(data.catatan || 'Tidak ada catatan')}</div></div>
        <div class="info-row"><div class="info-label">Status Akhir</div><div class="info-value"><span style="font-weight:700;color:${statusColor};">${statusLabel}</span></div></div>

        <div class="pdf-footer">Dibuat oleh: MAVII Field Service Management System &bull; ${dateStr}</div>
    `;

    return `<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pekerjaan ${taskCode}</title>
    <style>${pdfStyles}</style>
</head>
<body>${pdfBody}</body>
</html>`;
}

// Buka modal share laporan
window.openShareModal = function () {
    if (!currentTaskId) return;
    document.getElementById('shareModal').classList.add('active');
}

window.closeShareModal = function () {
    document.getElementById('shareModal').classList.remove('active');
}

function setBtnLoading(selector, isLoading, originalHtml) {
    const btn = document.querySelector(selector);
    if (!btn) return;
    btn.disabled = isLoading;
    btn.innerHTML = isLoading ? '<i class="bi bi-hourglass-split"></i> Memproses...' : originalHtml;
}

function loadScript(src) {
    return new Promise((resolve, reject) => {
        if (document.querySelector(`script[src="${src}"]`)) { resolve(); return; }
        const s = document.createElement('script');
        s.src = src;
        s.onload = resolve;
        s.onerror = () => reject(new Error(`Gagal load: ${src}`));
        document.head.appendChild(s);
    });
}

async function generatePdfBlob(data) {
    await loadScript('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js');
    await loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js');

    const { jsPDF } = window.jspdf;
    const iframe = document.createElement('iframe');
    iframe.style.cssText = 'position:fixed;left:-9999px;top:-9999px;width:794px;height:1123px;border:none;visibility:hidden;';
    document.body.appendChild(iframe);
    iframe.contentDocument.open();
    iframe.contentDocument.write(buildPdfHtml(data));
    iframe.contentDocument.close();
    await new Promise(r => setTimeout(r, 900));

    const canvas = await html2canvas(iframe.contentDocument.body, {
        scale: 2, useCORS: true, allowTaint: true,
        backgroundColor: '#ffffff', width: 794, windowWidth: 794
    });
    document.body.removeChild(iframe);

    const imgData = canvas.toDataURL('image/jpeg', 0.95);
    const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
    const pdfW = pdf.internal.pageSize.getWidth();
    const pdfH = (canvas.height * pdfW) / canvas.width;
    const pageH = pdf.internal.pageSize.getHeight();

    if (pdfH <= pageH + 10) {
        pdf.addImage(imgData, 'JPEG', 0, 0, pdfW, Math.min(pdfH, pageH));
    } else {
        let yOffset = 0;
        while (yOffset < pdfH) {
            if (yOffset > 0) pdf.addPage();
            pdf.addImage(imgData, 'JPEG', 0, -yOffset, pdfW, pdfH);
            yOffset += pageH;
        }
    }
    return pdf.output('blob');
}

async function shareFileNative(data, filename, shareOptions) {
    let pdfBlob = await generatePdfBlob(data);
    if (!pdfBlob || pdfBlob.size === 0) {
        pdfBlob = await generatePdfBlob(data);
        if (!pdfBlob || pdfBlob.size === 0) {
            throw new Error('File PDF kosong setelah dua kali percobaan');
        }
    }
    const pdfFile = new File([pdfBlob], filename, { type: 'application/pdf' });
    if (navigator.canShare && navigator.canShare({ files: [pdfFile] })) {
        await navigator.share({ files: [pdfFile], ...shareOptions });
        return 'shared';
    }
    const url = URL.createObjectURL(pdfBlob);
    const a = Object.assign(document.createElement('a'), { href: url, download: filename });
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    return 'downloaded';
}

window.savePdf = async function () {
    if (!currentTaskData) { showToast('Data belum dimuat.', 'warning'); return; }
    const taskCode = `TK-${String(currentTaskData.id).padStart(4, '0')}`;
    setBtnLoading('.share-btn-pdf', true, '<i class="bi bi-file-earmark-pdf-fill"></i> Simpan');
    try {
        const pdfBlob = await generatePdfBlob(currentTaskData);
        if (!pdfBlob || pdfBlob.size === 0) throw new Error('PDF kosong');
        const url = URL.createObjectURL(pdfBlob);
        const a = Object.assign(document.createElement('a'), { href: url, download: `laporan-${taskCode}.pdf` });
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        closeShareModal();
        showToast('PDF berhasil disimpan', 'success');
    } catch (e) {
        console.error(e);
        showToast('Gagal membuat PDF. Coba refresh halaman.', 'error');
    } finally {
        setBtnLoading('.share-btn-pdf', false, '<i class="bi bi-file-earmark-pdf-fill"></i> Simpan PDF');
    }
}

// Share laporan via WhatsApp
window.shareViaWhatsApp = async function () {
    if (!currentTaskData) { showToast('Data belum dimuat.', 'warning'); return; }
    const taskCode = `TK-${String(currentTaskData.id).padStart(4, '0')}`;
    setBtnLoading('.share-btn-whatsapp', true, '<i class="bi bi-whatsapp"></i> WhatsApp');
    try {
        const result = await shareFileNative(currentTaskData, `laporan-${taskCode}.pdf`,
            { title: `Laporan ${taskCode}`, text: `Halo, terlampir Laporan Pekerjaan ${taskCode}.` });
        closeShareModal();
        if (result === 'downloaded') {
            const text = `Halo, terlampir Laporan Pekerjaan ${taskCode}.\nFile PDF sudah terdownload, silakan lampirkan secara manual.`;
            setTimeout(() => window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank'), 300);
        } else {
            showToast('Laporan berhasil dibagikan', 'success');
        }
    } catch (e) {
        if (e.name !== 'AbortError') {
            console.error(e);
            showToast(e.message || 'Gagal membagikan PDF.', 'error');
        }
    } finally {
        setBtnLoading('.share-btn-whatsapp', false, '<i class="bi bi-whatsapp"></i> WhatsApp');
    }
}

window.shareViaEmail = function () {
    if (!currentTaskData) { showToast('Data belum dimuat.', 'warning'); return; }

    const data = currentTaskData;
    const taskCode = `TK-${String(data.id).padStart(4, '0')}`;

    // Format tindakan
    let actionsText = 'Tidak ada tindakan';
    if (data.actions) {
        if (Array.isArray(data.actions)) {
            actionsText = data.actions.join(', ');
        } else if (typeof data.actions === 'string') {
            try {
                const parsed = JSON.parse(data.actions);
                actionsText = Array.isArray(parsed) ? parsed.join(', ') : data.actions;
            } catch (e) {
                actionsText = data.actions;
            }
        }
    }

    const subject = `Laporan Pekerjaan - ${data.customer_name || 'Pelanggan'} - MAVII`;

    const body = `Yth. ${data.customer_name || 'Pelanggan'},

Berikut adalah ringkasan laporan pekerjaan:

Pelanggan: ${data.customer_name || '-'}
Gangguan: ${data.title || '-'}
Status: ${data.status === 'canceled' || data.status === 'rejected' ? 'Canceled' : 'Finished'}
Teknisi: ${data.technician_name || '-'}
Tanggal: ${data.completed_at || '-'}

Tindakan: ${actionsText}
Catatan: ${data.catatan || 'Tidak ada catatan'}

Terima kasih.

MAVII - Field Service Management`;

    window.location.href = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
    closeShareModal();
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelector('.filter-btn-all')?.classList.add('active');
    document.getElementById('jobDetailsPanel')?.classList.remove('active');

    document.getElementById('shareModal')?.addEventListener('click', function (e) {
        if (e.target === this) closeShareModal();
    });

    document.getElementById('datePickerModal')?.addEventListener('click', function (e) {
        if (e.target === this) closeDatePicker();
    });
});

function showPhotoModal(imageUrl, note) {
    const existingModal = document.getElementById('photoModal');
    if (existingModal) existingModal.remove();

    const modal = document.createElement('div');
    modal.id = 'photoModal';
    modal.style.cssText = `
        position: fixed; top:0; left:0; width:100%; height:100%;
        background: rgba(0,0,0,0.9); z-index: 999999;
        display: flex; justify-content: center; align-items: center;
        cursor: pointer;
    `;

    const content = document.createElement('div');
    content.style.cssText = `
        max-width: 90%; max-height: 90%; position: relative;
        display: flex; flex-direction: column; align-items: center;
    `;

    const img = document.createElement('img');
    img.src = imageUrl;
    img.style.cssText = `
        max-width: 100%; max-height: 80vh; border-radius: 12px;
        box-shadow: 0 0 20px rgba(0,0,0,0.5);
    `;

    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '&times;';
    closeBtn.style.cssText = `
        position: absolute; top: -40px; right: 0;
        background: #1b1fb8; color: white; border: none;
        width: 36px; height: 36px; border-radius: 50%;
        font-size: 28px; font-weight: bold; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    `;
    closeBtn.onclick = (e) => {
        e.stopPropagation();
        modal.remove();
    };

    const caption = document.createElement('p');
    caption.textContent = note || '';
    caption.style.cssText = `
        color: white; margin-top: 12px; font-size: 14px;
        background: rgba(0,0,0,0.6); padding: 6px 12px;
        border-radius: 20px;
    `;

    content.appendChild(img);
    content.appendChild(caption);
    content.appendChild(closeBtn);
    modal.appendChild(content);

    modal.onclick = (e) => {
        if (e.target === modal) modal.remove();
    };

    document.body.appendChild(modal);
}

document.addEventListener('click', function (e) {
    const target = e.target;
    if (target.classList && target.classList.contains('bukti-foto-img')) {
        e.preventDefault();
        const parentDiv = target.closest('div');
        const noteSpan = parentDiv?.querySelector('span');
        const note = noteSpan ? noteSpan.textContent : '';
        showPhotoModal(target.src, note);
    }
});