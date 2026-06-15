import './bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
window.L = L;

window.confirmLogout = function(e) {
    e.preventDefault();
    document.getElementById('logoutModal').style.display = 'flex';
}

window.closeLogout = function() {
    document.getElementById('logoutModal').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('logoutModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeLogout();
        });
    }
});