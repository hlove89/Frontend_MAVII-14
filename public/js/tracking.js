function initTracking(config) {
    const { API_BASE_URL, ACCESS_TOKEN } = config;
    let fullMapInstance = null;

    // Escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Ambil dan tampilkan data teknisi aktif
    async function loadTechnicians() {
        try {
            const response = await axios.get(`${API_BASE_URL}/api/admin/tracking?active_only=1`, {
                headers: {
                    'Authorization': `Bearer ${ACCESS_TOKEN}`,
                    'Accept': 'application/json'
                }
            });

            const technicians = response.data.data;
            const container = document.getElementById('technicianContainer');
            if (!container) return;

            container.innerHTML = '';

            if (!technicians || technicians.length === 0) {
                container.innerHTML = `
                    <div class="empty-state-wrapper" style="grid-column: 1 / -1; width: 100%;">
                        <div class="empty-state" style="background: #fff; border-radius: 16px; padding: 50px; text-align: center; width: 100%;">
                            <i class="bi bi-wifi-off" style="font-size: 48px; color: #ccc; margin-bottom: 12px; display: block;"></i>
                            <p style="margin-top: 12px; color: #888; font-size: 16px; font-weight: 500;">Tidak ada teknisi yang sedang dalam perjalanan atau mengerjakan tugas.</p>
                            <p style="font-size: 12px; margin-top: 8px; color: #aaa;">Teknisi akan muncul setelah mereka menerima tugas dan mulai bekerja.</p>
                        </div>
                    </div>`;
                return;
            }

            technicians.forEach((tech) => {
                // Abaikan teknisi status assigned
                if (!tech.task_status || tech.task_status === 'assigned') {
                    return; 
                }

                const card = document.createElement('div');
                card.className = 'tech-card';
                card.dataset.tech = JSON.stringify(tech);

                card.innerHTML = `
                    <div class="card-header">
                        <span class="location-label">LOKASI TERKINI</span>
                        <span class="online-badge">Online</span>
                    </div>
                    <div class="map-container">
                        <button class="map-expand-btn" onclick="openFullMapModal(${tech.id}, this)">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </button>
                        <div class="mini-map">
                            <div id="map-${tech.id}" class="leaflet-map"></div>
                        </div>
                    </div>
                    <div class="tech-info">
                        <div class="tech-name">${escapeHtml(tech.name)}</div>
                        <div class="tech-role">Teknisi</div>
                        <div class="gangguan-label">GANGGUAN</div>
                        <div class="gangguan-text">${escapeHtml(tech.current_task || 'Tidak ada tugas')}</div>
                        ${tech.task_status && tech.task_status !== 'assigned' ? `<div class="status-badge status-${tech.task_status.toLowerCase()}">${tech.task_status}</div>` : ''}
                    </div>
                    <div class="card-footer">
                        <a href="#" class="see-more-link" onclick="viewDetail(event, ${tech.id}, this)">
                            See More →
                        </a>
                    </div>
                `;
                container.appendChild(card);

                // Init Map Mini
                setTimeout(() => {
                    initLeafletMap(`map-${tech.id}`, tech, false);
                }, 100);
            });

        } catch (error) {
            console.error('Error fetching technicians:', error);
            const container = document.getElementById('technicianContainer');
            if (container) {
                container.innerHTML = '<p style="color:red; text-align:center;">Gagal mengambil data dari API Backend.</p>';
            }
        }
    }

    // Inisialisasi map leaflet untuk teknisi (mini atau full)
    function initLeafletMap(containerId, tech, isFull) {
        const techLoc = [parseFloat(tech.tech_latitude), parseFloat(tech.tech_longitude)];
        const destLoc = [parseFloat(tech.task_latitude), parseFloat(tech.task_longitude)];

        // Validasi koordinat teknisi
        if (isNaN(techLoc[0]) || isNaN(techLoc[1])) return;

        const map = L.map(containerId, {
            zoomControl: true,
            attributionControl: false
        }).setView(techLoc, 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        // Markers
        const techIcon = L.divIcon({
            className: 'custom-tech-marker',
            iconSize: [isFull ? 20 : 18, isFull ? 20 : 18],
            iconAnchor: [isFull ? 10 : 9, isFull ? 10 : 9]
        });
        L.marker(techLoc, { icon: techIcon }).addTo(map).bindPopup(escapeHtml(tech.name));

        // Marker tujuan jika valid
        if (!isNaN(destLoc[0]) && !isNaN(destLoc[1])) {
            const destIcon = L.divIcon({
                className: 'custom-dest-marker',
                iconSize: [isFull ? 20 : 14, isFull ? 20 : 14],
                iconAnchor: [isFull ? 10 : 7, isFull ? 10 : 7]
            });
            L.marker(destLoc, { icon: destIcon }).addTo(map).bindPopup('Lokasi Tugas');

            // Fetch Rute dari OSRM
            const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${techLoc[1]},${techLoc[0]};${destLoc[1]},${destLoc[0]}?overview=full&geometries=geojson`;

            fetch(osrmUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.code === 'Ok' && data.routes.length > 0) {
                        const coordinates = data.routes[0].geometry.coordinates.map(coord => [coord[1], coord[0]]);
                        const polyline = L.polyline(coordinates, {
                            color: '#1b1fb8',
                            weight: isFull ? 6 : 3,
                            opacity: 0.9,
                            lineJoin: 'round'
                        }).addTo(map);

                        map.fitBounds(polyline.getBounds(), { padding: [30, 30] });
                    } else {
                        // Fallback garis lurus
                        const fallbackPath = L.polyline([techLoc, destLoc], {
                            color: '#1b1fb8',
                            weight: isFull ? 6 : 3,
                            opacity: 0.9
                        }).addTo(map);
                        map.fitBounds(fallbackPath.getBounds(), { padding: [30, 30] });
                    }
                })
                .catch(err => {
                    console.error('Routing error:', err);
                    const fallbackPath = L.polyline([techLoc, destLoc], {
                        color: '#1b1fb8',
                        weight: isFull ? 6 : 3,
                        opacity: 0.9
                    }).addTo(map);
                    map.fitBounds(fallbackPath.getBounds(), { padding: [30, 30] });
                });
        }

        if (isFull) fullMapInstance = map;
        return map;
    }

    // Modal Functions
    // Modal Functions
    // Tampilkan detail tugas teknisi di modal
    window.viewDetail = async function (event, techId, btn) {
        event.preventDefault();
        const card = btn.closest('.tech-card');
        const tech = JSON.parse(card.dataset.tech);
        const modal = document.getElementById('detailModal');
        const body = document.getElementById('detailModalBody');

        body.innerHTML = '<div style="padding:40px; text-align:center;"><i class="bi bi-hourglass-split" style="font-size:24px;"></i> Memuat data...</div>';
        modal.classList.add('active');

        let proofs = [];
        if (tech.active_task_id) {
            try {
                const res = await fetch(`${API_BASE_URL.replace(/\/$/, '')}/api/admin/tasks/${tech.active_task_id}`, {
                    headers: { 'Authorization': `Bearer ${ACCESS_TOKEN}`, 'Accept': 'application/json' }
                });
                const result = await res.json();
                if (result.task && result.task.proofs) {
                    proofs = result.task.proofs;
                }
            } catch (e) { console.error('Gagal fetch proofs:', e); }
        }

        const status = (tech.task_status || 'assigned').toLowerCase();
        let statusBg = '#cce5ff';
        if (status === 'accepted') statusBg = '#fef9c3';
        else if (status === 'on-going') statusBg = '#ffedd5';
        else if (status === 'completed') statusBg = '#dcfce7';

        let actions = tech.actions;
        if (typeof actions === 'string') {
            try {
                let parsed = JSON.parse(actions);
                actions = Array.isArray(parsed) ? parsed : [actions];
            } catch (e) { actions = [actions]; }
        } else if (!Array.isArray(actions)) actions = [];

        let actionsHtml = (actions.length > 0) ? `<ul>${actions.map(act => `<li><i class="bi bi-check-circle-fill" style="color:#10b981;"></i> ${escapeHtml(act)}</li>`).join('')}</ul>` : '<p style="color:#888; font-style:italic; font-size:13px;">Belum ada tindakan yang dicatat.</p>';

        let photoHtml = '';
        if (proofs.length > 0) {
            photoHtml = '<div style="display:flex; gap:10px; flex-wrap:wrap;">' + proofs.map(p => {
                const cleanPath = p.photo_path.replace(/^\//, '').replace(/^public\//, '').replace(/^storage\//, '');
                const photoUrl = `${API_BASE_URL.replace(/\/$/, '')}/storage/${cleanPath}`;
                return `
                <div class="photo-container" style="display:flex; flex-direction:column; gap:6px; flex: 0 0 calc(50% - 5px);">
                    <img src="${photoUrl}" alt="Bukti Foto" class="bukti-foto-img" style="width:100%; height:100px; object-fit:cover; border-radius:8px; border:1px solid #ddd; cursor:pointer;">
                    <span style="font-size:11px; color:#888; text-align:center;">${escapeHtml(p.note || 'Foto')}</span>
                </div>`;
            }).join('') + '</div>';
        } else {
            photoHtml = '<div class="photo-placeholder" style="text-align:center; padding:30px; background:#fafafa; border-radius:12px; border:1px dashed #ddd;"><i class="bi bi-camera-fill" style="font-size:48px; color:#ccc; display:block; margin-bottom:10px;"></i><span style="color:#999; font-size:13px;">Belum ada bukti foto</span></div>';
        }

        body.innerHTML = `
            <div class="detail-section"><h4>INFORMASI TEKNISI</h4>
                <ul>
                    <li><i class="bi bi-person-circle"></i> <strong>${escapeHtml(tech.name)}</strong></li>
                    <li><i class="bi bi-envelope"></i> ${escapeHtml(tech.email)}</li>
                    <li><i class="bi bi-phone"></i> ${escapeHtml(tech.phone || '-')}</li>
                </ul>
            </div>
            <div class="detail-section"><h4>DETAIL PEKERJAAN</h4>
                <ul>
                    <li><i class="bi bi-tag"></i> <strong>${escapeHtml(tech.current_task || 'Tidak ada')}</strong></li>
                    <li><i class="bi bi-geo-alt"></i> ${escapeHtml(tech.task_address || 'Alamat tidak tersedia')}</li>
                    <li><i class="bi bi-flag"></i> Status:
                        <span style="background:${statusBg};color:#000;padding:4px 12px;border-radius:30px;font-weight:700;">${tech.task_status || 'Assigned'}</span>
                    </li>
                </ul>
            </div>
            <div class="detail-section"><h4>TINDAKAN</h4>${actionsHtml}</div>
            <div class="detail-section"><h4>CATATAN</h4><div class="catatan-box">${escapeHtml(tech.catatan || 'Tidak ada catatan')}</div></div>
            <div class="detail-section"><h4>BUKTI FOTO</h4>${photoHtml}</div>
        `;
    };

    // Buka modal map layar penuh
    window.openFullMapModal = function (techId, btn) {
        const card = btn.closest('.tech-card');
        const tech = JSON.parse(card.dataset.tech);
        const modal = document.getElementById('fullMapModal');
        const body = document.getElementById('fullMapBody');
        const title = document.getElementById('fullMapTitle');

        title.innerText = 'Lokasi ' + tech.name;
        body.innerHTML = '<div id="fullMapInstance" style="width:100%; height:100%;"></div>';
        modal.classList.add('active');

        setTimeout(() => {
            if (fullMapInstance) fullMapInstance.remove();
            initLeafletMap('fullMapInstance', tech, true);
        }, 300);
    };

    window.closeFullMapModal = function () {
        if (fullMapInstance) fullMapInstance.remove();
        fullMapInstance = null;
        document.getElementById('fullMapModal').classList.remove('active');
    };

    window.closeDetailModal = function () {
        document.getElementById('detailModal').classList.remove('active');
    };

    window.onclick = function (event) {
        if (event.target == document.getElementById('detailModal')) closeDetailModal();
        if (event.target == document.getElementById('fullMapModal')) closeFullMapModal();
    };

    function showTrackingPhotoModal(imageUrl, note) {
        const existingModal = document.getElementById('trackingPhotoModal');
        if (existingModal) existingModal.remove();

        const modal = document.createElement('div');
        modal.id = 'trackingPhotoModal';
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
        // Cek klik foto tracking
        if (target.classList && target.classList.contains('bukti-foto-img') && target.closest('.photo-container')) {
            e.preventDefault();
            const parentDiv = target.closest('.photo-container');
            const noteSpan = parentDiv?.querySelector('span');
            const note = noteSpan ? noteSpan.textContent : '';
            showTrackingPhotoModal(target.src, note);
        }
    });

    loadTechnicians();
}