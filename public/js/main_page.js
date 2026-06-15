document.addEventListener('DOMContentLoaded', function () {

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

    const pinIcon = L.divIcon({
        className: '',
        html: `<div style="
            width: 28px; height: 28px;
            background: #cc2200;
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            border: 3px solid #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        "></div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 28],
    });

    const points = window.technicianPoints && window.technicianPoints.length
        ? window.technicianPoints
        : [];

    points.forEach(function (p) {
        L.marker([p.lat, p.lng], { icon: pinIcon })
            .addTo(map)
            .bindPopup(`
                <div style="min-width:140px; font-family:sans-serif;">
                    <strong style="font-size:13px;">${p.label}</strong><br>
                    <small style="color:#666;">${p.status}</small>
                </div>
            `);
    });

});