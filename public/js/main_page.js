document.addEventListener('DOMContentLoaded', function () {
    // Inisialisasi peta Leaflet untuk dashboard
    const map = L.map('globalMap', {
        center: [-6.3, 107.3],
        zoom: 13,
        zoomControl: true,
        scrollWheelZoom: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    if (window.technicianPoints && window.technicianPoints.length > 0) {
        // Tampilkan marker teknisi yang ada
        const markers = [];
        window.technicianPoints.forEach(function (p) {
            if (p.lat && p.lng) {
                const pinColor = p.is_busy ? '#F24444' : '#10B981';

                const pinIcon = L.divIcon({
                    className: '',
                    html: `<div style="
                        width: 28px; height: 28px;
                        background: ${pinColor};
                        border-radius: 50% 50% 50% 0;
                        transform: rotate(-45deg);
                        border: 3px solid #fff;
                        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                    "></div>`,
                    iconSize: [28, 28],
                    iconAnchor: [14, 28],
                });

                const m = L.marker([p.lat, p.lng], { icon: pinIcon })
                    .bindPopup(`
                        <div style="min-width:140px; font-family:sans-serif;">
                            <strong style="font-size:13px;">${p.label}</strong><br>
                            <small style="color:#666;">${p.status}</small>
                        </div>
                    `);
                markers.push(m);
                m.addTo(map);
            }
        });

        if (markers.length > 0) {
            // Sesuaikan zoom level agar semua marker terlihat
            const group = new L.featureGroup(markers);
            map.fitBounds(group.getBounds(), { padding: [50, 50] });
        }
    }

});