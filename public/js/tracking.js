function initTracking(config) {
    const { API_BASE_URL, ACCESS_TOKEN } = config;
    let fullMapInstance = null;

    // Helper untuk escape HTML agar aman
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    async function loadTechnicians() {
        try {
            const response = await axios.get(`${API_BASE_URL}/api/admin/tracking`, {
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
                    <div class="empty-state">
                        <i class="bi bi-wifi-off"></i>
                        <p>Tidak ada teknisi yang aktif saat ini.</p>
                    </div>`;
                return;
            }

            technicians.forEach((tech) => {
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
                        <div class="status-badge status-${(tech.task_status || 'assigned').toLowerCase()}">${tech.task_status || 'Assigned'}</div>
                    </div>
                    <div class="card-footer">
                        <a href="#" class="see-more-link" onclick="viewDetail(event, ${tech.id}, this)">
                            See More →
                        </a>
                    </div>
                `;
                container.appendChild(card);

                // Inisialisasi Map Mini
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

    function initLeafletMap(containerId, tech, isFull) {
        const techLoc = [parseFloat(tech.tech_latitude), parseFloat(tech.tech_longitude)];
        const destLoc = [parseFloat(tech.task_latitude), parseFloat(tech.task_longitude)];
        
        // Cek jika koordinat valid
        if (isNaN(techLoc[0]) || isNaN(destLoc[0])) return;

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

        const destIcon = L.divIcon({
            className: 'custom-dest-marker',
            iconSize: [isFull ? 20 : 14, isFull ? 20 : 14],
            iconAnchor: [isFull ? 10 : 7, isFull ? 10 : 7]
        });
        L.marker(destLoc, { icon: destIcon }).addTo(map).bindPopup('Lokasi Tugas');

        // Fetch Routing dari OSRM (Jalur Jalan Raya)
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
                    // Fallback ke garis lurus jika OSRM gagal
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
        
        if (isFull) fullMapInstance = map;
        return map;
    }

    // Modal Functions
    window.viewDetail = function(event, techId, btn) {
        event.preventDefault();
        const card = btn.closest('.tech-card');
        const tech = JSON.parse(card.dataset.tech);
        const modal = document.getElementById('detailModal');
        const body = document.getElementById('detailModalBody');
        
        const status = (tech.task_status || 'assigned').toLowerCase();
        let statusBg = '#cce5ff';
        if (status === 'accepted') statusBg = '#fef9c3';
        else if (status === 'on-going') statusBg = '#ffedd5';
        else if (status === 'completed') statusBg = '#dcfce7';

        // Tindakan
        let actions = tech.actions;
        if (typeof actions === 'string') {
            try { actions = JSON.parse(actions); } catch(e) { actions = []; }
        }
        let actionsHtml = '';
        if (Array.isArray(actions) && actions.length > 0) {
            actionsHtml = '<ul>';
            actions.forEach(act => {
                actionsHtml += `<li><i class="bi bi-check-circle-fill" style="color:#10b981;"></i> ${escapeHtml(act)}</li>`;
            });
            actionsHtml += '</ul>';
        } else {
            actionsHtml = '<p style="color:#888; font-style:italic; font-size:13px;">Belum ada tindakan yang dicatat.</p>';
        }

        // Bukti Foto
        let photoHtml = '';
        if (tech.proof_photo) {
            const photoUrl = tech.proof_photo.startsWith('http') ? tech.proof_photo : `${API_BASE_URL}/api/technician/proof/${tech.proof_photo.split('/').pop()}`;
            photoHtml = `<div class="photo-container"><img src="${photoUrl}" alt="Bukti Foto" style="width:100%; border-radius:12px; border:1px solid #eee;"></div>`;
        } else {
            photoHtml = `
                <div class="photo-placeholder" style="text-align:center; padding:30px; background:#fafafa; border-radius:12px; border:1px dashed #ddd;">
                    <i class="bi bi-camera-fill" style="font-size:48px; color:#ccc; display:block; margin-bottom:10px;"></i>
                    <span style="color:#999; font-size:13px;">Belum ada bukti foto</span>
                </div>`;
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
            <div class="detail-section"><h4>TINDAKAN</h4>
                ${actionsHtml}
            </div>
            <div class="detail-section"><h4>CATATAN</h4>
                <div class="catatan-box">${escapeHtml(tech.catatan || 'Tidak ada catatan')}</div>
            </div>
            <div class="detail-section"><h4>BUKTI FOTO</h4>
                ${photoHtml}
            </div>
        `;
        modal.classList.add('active');
    };

    window.openFullMapModal = function(techId, btn) {
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

    window.closeFullMapModal = function() {
        if (fullMapInstance) fullMapInstance.remove();
        fullMapInstance = null;
        document.getElementById('fullMapModal').classList.remove('active');
    };

    window.closeDetailModal = function() {
        document.getElementById('detailModal').classList.remove('active');
    };

    window.onclick = function(event) {
        if (event.target == document.getElementById('detailModal')) closeDetailModal();
        if (event.target == document.getElementById('fullMapModal')) closeFullMapModal();
    };

    loadTechnicians();
}