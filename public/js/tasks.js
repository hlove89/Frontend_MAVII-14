function showToast(msg, type){
    var old=document.getElementById('toastNotif'); if(old) old.remove();
    var toast=document.createElement('div');
    toast.id='toastNotif';
    toast.className='toast-notification toast-'+(type||'success');
    toast.innerHTML=msg;
    document.body.appendChild(toast);
    setTimeout(function(){toast.classList.add('show');},10);
    setTimeout(function(){toast.remove();},3000);
}

window.selectTechnician = function(btn) {
    var card = btn.closest('.technician-card');
    if (!card) return;
    if (card.dataset.status === 'busy') {
        showToast('Teknisi sedang bertugas!', 'error');
        return;
    }
    
    var techId = card.dataset.id;
    var techName = card.dataset.name;
    
    document.getElementById('technicianSelect').value = techId;
    
    var avatarImg = card.querySelector('.technician-avatar img');
    var avatarHtml = '';
    
    if (avatarImg && avatarImg.src) {
        avatarHtml = '<img src="' + avatarImg.src + '" alt="avatar">';
    } else {
        avatarHtml = '<i class="bi bi-person-circle"></i>';
    }
    
    var header = document.getElementById('formHeader');
    header.style.display = 'flex';
    header.innerHTML = 
        '<div class="form-header-avatar">' + avatarHtml + '</div>' +
        '<div class="header-info">' +
            '<span class="tech-name">' + techName + '</span>' +
            '<span class="tech-role">Teknisi</span>' +
        '</div>' +
        '<span class="online-badge">Online</span>';
    
    document.querySelector('.form-section').style.display = 'block';
    document.querySelector('.task-form').style.display = 'block';
    document.querySelector('.form-section').scrollIntoView({behavior: 'smooth'});
}

document.addEventListener('DOMContentLoaded',function(){
    var success=document.querySelector('meta[name="flash-success"]');
    var error=document.querySelector('meta[name="flash-error"]');
    if(success) showToast(success.content,'success');
    if(error) showToast(error.content,'error');
});

var addrMap=null,addrGeocodeTimer=null,addrPendingAddr='',addrPendingLat=null,addrPendingLng=null;
var addrMarker=null;

window.openAddressSheet=function(){
    document.getElementById('addr-overlay').classList.add('open');
    setTimeout(function(){
        if(!addrMap){
            addrMap=L.map('addr-map').setView([-6.2088,106.8456],14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap'}).addTo(addrMap);
            
            addrMarker=L.marker([-6.2088,106.8456],{draggable:true}).addTo(addrMap);
            
            addrMap.on('moveend',function(){
                var c=addrMap.getCenter();
                addrMarker.setLatLng(c);
                clearTimeout(addrGeocodeTimer);
                addrGeocodeTimer=setTimeout(function(){
                    fetch('https://nominatim.openstreetmap.org/reverse?lat='+c.lat+'&lon='+c.lng+'&format=json')
                    .then(function(r){return r.json();})
                    .then(function(d){
                        addrPendingAddr=d.display_name||c.lat+', '+c.lng;
                        addrPendingLat=c.lat;
                        addrPendingLng=c.lng;
                        document.getElementById('addr-preview').innerHTML=addrPendingAddr;
                        document.getElementById('addr-confirm-btn').disabled=false;
                    })
                    .catch(function(){
                        addrPendingAddr=c.lat+', '+c.lng;
                        addrPendingLat=c.lat;
                        addrPendingLng=c.lng;
                        document.getElementById('addr-preview').innerHTML=addrPendingAddr;
                        document.getElementById('addr-confirm-btn').disabled=false;
                    });
                },500);
            });
            
            addrMarker.on('dragend',function(){
                var latlng=addrMarker.getLatLng();
                addrMap.setView([latlng.lat,latlng.lng]);
            });
        }else{
            addrMap.invalidateSize();
        }
    },100);
}

window.closeAddressSheet=function(){
    document.getElementById('addr-overlay').classList.remove('open');
}

window.handleAddrOverlayClick=function(e){
    if(e.target===document.getElementById('addr-overlay')) closeAddressSheet();
}

window.addrUseMyLocation=function(){
    navigator.geolocation.getCurrentPosition(function(pos){
        addrMap.setView([pos.coords.latitude,pos.coords.longitude],16);
        addrMarker.setLatLng([pos.coords.latitude,pos.coords.longitude]);
    });
}

window.addrFlyTo = function(lat, lng) {
    document.getElementById('addr-sug-list').style.display = 'none';
    document.getElementById('addr-search-input').value = '';
    addrMap.setView([lat, lng], 16);
    addrMarker.setLatLng([lat, lng]);
}

window.onAddrSearch=function(q){
    if(q.length<3){
        document.getElementById('addr-sug-list').style.display='none';
        return;
    }
    clearTimeout(addrGeocodeTimer);
    addrGeocodeTimer=setTimeout(function(){
        fetch('https://nominatim.openstreetmap.org/search?q='+encodeURIComponent(q)+'&format=json&limit=5&accept-language=id')
        .then(function(r){return r.json();})
        .then(function(results){
            var list=document.getElementById('addr-sug-list');
            if(!results.length){
                list.style.display='none';
                return;
            }
            list.innerHTML=results.map(function(r){
                var parts=r.display_name.split(',');
                var name=parts[0];
                var addr=parts.slice(1,4).join(',').trim();
                return '<div class="sug-item" onclick="addrFlyTo('+r.lat+','+r.lon+')">' +
                    '<div class="sug-ico">📍</div>' +
                    '<div>' +
                    '<div class="sug-name">'+name+'</div>' +
                    '<div class="sug-addr">'+addr+'</div>' +
                    '</div>' +
                    '</div>';
            }).join('');
            list.style.display='block';
        }).catch(function(){});
    },400);
}

window.confirmAddrLocation=function(){
    if(!addrPendingAddr) return;
    var parts=addrPendingAddr.split(',');
    document.getElementById('loc-main').innerHTML=parts[0];
    document.getElementById('loc-main').classList.remove('ph');
    document.getElementById('loc-sub').innerHTML=parts.slice(1,3).join(',');
    document.getElementById('address-hidden').value=addrPendingAddr;
    if(addrPendingLat!==null && addrPendingLng!==null){
        document.getElementById('lat-hidden').value=addrPendingLat;
        document.getElementById('lng-hidden').value=addrPendingLng;
    }
    closeAddressSheet();
}