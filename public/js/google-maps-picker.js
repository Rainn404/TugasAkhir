/**
 * Arsy Tenda - Google Maps Platform Interactive Location Picker
 * Integrates: Maps JavaScript API, Geocoding API, and Routes API.
 */

(function () {
    'use strict';

    let map = null;
    let customerMarker = null;
    let baseMarker = null;
    let routePolyline = null;
    let baseInfoWindow = null;
    let customerInfoWindow = null;

    window.gm_authFailure = function () {
        console.warn('Google Maps billing / auth failure detected in picker.');
        const alertBox = document.getElementById('mapAlertBox');
        if (alertBox) {
            alertBox.className = 'alert alert-warning py-2 px-3 small rounded-3 d-flex align-items-center gap-2 mb-3';
            alertBox.innerHTML = `
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div class="flex-grow-1">Google Maps memerlukan <strong>Billing Account</strong> aktif di Google Cloud Console. Anda tetap dapat mengisi alamat secara manual di bawah ini.</div>
                <button type="button" class="btn-close" style="font-size: 0.75rem;" onclick="this.parentElement.classList.add('d-none')"></button>
            `;
            alertBox.classList.remove('d-none');
        }
    };

    window.initGoogleMap = function () {
        const mapContainer = document.querySelector('[data-google-map]');
        if (!mapContainer) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const geocodeUrl = mapContainer.dataset.geocodeUrl || '/maps/geocode';
        const reverseGeocodeUrl = mapContainer.dataset.reverseGeocodeUrl || '/maps/reverse-geocode';
        const routeUrl = mapContainer.dataset.routeUrl || '/maps/calculate-route';

        const baseLat = parseFloat(mapContainer.dataset.baseLat) || -3.7955;
        const baseLng = parseFloat(mapContainer.dataset.baseLng) || 114.7712;
        const baseName = mapContainer.dataset.baseName || 'Kantor Arsy Tenda Pelaihari';

        const latInput = document.getElementById(mapContainer.dataset.inputLat || 'latInput');
        const lngInput = document.getElementById(mapContainer.dataset.inputLng || 'lngInput');
        const addressInput = document.getElementById(mapContainer.dataset.inputAddress || 'addressInput');
        const coordsDisplay = document.getElementById('coordsDisplay');
        const searchInput = document.getElementById('mapSearchInput');
        const btnSearch = document.getElementById('btnMapSearch');
        const btnGps = document.getElementById('btnMapGps');
        const routeInfoBox = document.getElementById('mapRouteInfoBox');
        const alertBox = document.getElementById('mapAlertBox');

        let initialLat = parseFloat(latInput?.value) || baseLat;
        let initialLng = parseFloat(lngInput?.value) || baseLng;

        function showAlert(message, type = 'warning') {
            if (!alertBox) return;
            alertBox.className = `alert alert-${type} py-2 px-3 small rounded-3 d-flex align-items-center gap-2 mb-3`;
            alertBox.innerHTML = `
                <i class="bi bi-${type === 'danger' ? 'exclamation-triangle-fill' : (type === 'success' ? 'check-circle-fill' : 'info-circle-fill')}"></i>
                <div class="flex-grow-1">${message}</div>
                <button type="button" class="btn-close btn-close-white ms-auto" style="filter: invert(1); font-size: 0.75rem;" onclick="this.parentElement.classList.add('d-none')"></button>
            `;
            alertBox.classList.remove('d-none');
        }

        function hideAlert() {
            if (alertBox) alertBox.classList.add('d-none');
        }

        function updateCoordsFields(lat, lng) {
            if (latInput) latInput.value = Number(lat).toFixed(7);
            if (lngInput) lngInput.value = Number(lng).toFixed(7);
            if (coordsDisplay) {
                coordsDisplay.textContent = `Lat: ${Number(lat).toFixed(4)}, Lng: ${Number(lng).toFixed(4)}`;
            }
        }

        function updateMarkerPosition(marker, pos) {
            if (!marker) return;
            if (typeof marker.setPosition === 'function') {
                marker.setPosition(pos);
            } else {
                marker.position = pos;
            }
        }

        // Initialize Map
        try {
            map = new google.maps.Map(mapContainer, {
                center: { lat: initialLat, lng: initialLng },
                zoom: 14,
                mapId: 'DEMO_MAP_ID',
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
                zoomControl: true,
            });
        } catch (e) {
            console.error('Google Maps initialization failed:', e);
            showAlert('Layanan Google Maps gagal dimuat. Anda dapat memasukkan alamat secara manual.', 'danger');
            return;
        }

        baseInfoWindow = new google.maps.InfoWindow({
            content: `<div style="font-family: sans-serif; font-size: 13px; line-height: 1.4; padding: 4px;">
                <strong>${baseName}</strong><br>
                <span style="color: #666;">Titik Asal Logistik & Pengiriman</span>
            </div>`
        });

        customerInfoWindow = new google.maps.InfoWindow({
            content: `<div style="font-family: sans-serif; font-size: 13px; padding: 4px;">
                <strong>Titik Lokasi Acara</strong><br>
                <span style="color: #666;">Geser pin ini ke lokasi yang tepat</span>
            </div>`
        });

        if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
            // Base Marker using AdvancedMarkerElement
            baseMarker = new google.maps.marker.AdvancedMarkerElement({
                position: { lat: baseLat, lng: baseLng },
                map: map,
                title: baseName,
            });
            baseMarker.addListener('click', () => {
                baseInfoWindow.open({ anchor: baseMarker, map: map });
            });

            // Customer Marker using AdvancedMarkerElement
            customerMarker = new google.maps.marker.AdvancedMarkerElement({
                position: { lat: initialLat, lng: initialLng },
                map: map,
                gmpDraggable: true,
                title: 'Titik Lokasi Acara Anda',
            });
            customerMarker.addListener('click', () => {
                customerInfoWindow.open({ anchor: customerMarker, map: map });
            });
            customerMarker.addListener('dragend', function (event) {
                const lat = event?.latLng ? event.latLng.lat() : customerMarker.position.lat;
                const lng = event?.latLng ? event.latLng.lng() : customerMarker.position.lng;
                onLocationSelected(lat, lng, true);
            });
        } else {
            // Fallback for legacy google.maps.Marker
            baseMarker = new google.maps.Marker({
                position: { lat: baseLat, lng: baseLng },
                map: map,
                title: baseName,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 9,
                    fillColor: '#191919',
                    fillOpacity: 1,
                    strokeWeight: 2,
                    strokeColor: '#FFFFFF',
                },
                zIndex: 1
            });
            baseMarker.addListener('click', () => {
                baseInfoWindow.open(map, baseMarker);
            });

            customerMarker = new google.maps.Marker({
                position: { lat: initialLat, lng: initialLng },
                map: map,
                draggable: true,
                title: 'Titik Lokasi Acara Anda',
                animation: google.maps.Animation.DROP,
                zIndex: 10
            });
            customerMarker.addListener('click', () => {
                customerInfoWindow.open(map, customerMarker);
            });
            customerMarker.addListener('dragend', function (event) {
                const lat = event.latLng.lat();
                const lng = event.latLng.lng();
                onLocationSelected(lat, lng, true);
            });
        }

        // Handle Map Click
        map.addListener('click', function (event) {
            const lat = event.latLng.lat();
            const lng = event.latLng.lng();
            updateMarkerPosition(customerMarker, event.latLng);
            onLocationSelected(lat, lng, true);
        });

        // Location selection handler
        function onLocationSelected(lat, lng, fetchReverseGeocode = true) {
            updateCoordsFields(lat, lng);
            hideAlert();

            if (fetchReverseGeocode) {
                doReverseGeocode(lat, lng);
            }

            calculateRoute(lat, lng);
        }

        // Reverse Geocoding via Backend
        function doReverseGeocode(lat, lng) {
            if (!addressInput) return;

            fetch(reverseGeocodeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ latitude: lat, longitude: lng })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.formatted_address) {
                    addressInput.value = data.formatted_address;
                }
            })
            .catch(err => {
                console.warn('Reverse geocoding error:', err);
            });
        }

        // Geocoding Search via Backend
        function doSearchAddress() {
            if (!searchInput) return;
            const query = searchInput.value.trim();
            if (!query) {
                showAlert('Silakan masukkan nama jalan atau patokan lokasi untuk mencari.', 'warning');
                return;
            }

            if (btnSearch) {
                btnSearch.disabled = true;
                btnSearch.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Mencari...';
            }

            fetch(geocodeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ address: query })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.latitude && data.longitude) {
                    const newPos = new google.maps.LatLng(data.latitude, data.longitude);
                    updateMarkerPosition(customerMarker, newPos);
                    map.panTo(newPos);
                    map.setZoom(15);
                    if (addressInput) addressInput.value = data.formatted_address || query;
                    onLocationSelected(data.latitude, data.longitude, false);
                    showAlert('Lokasi ditemukan pada peta: ' + (data.formatted_address || query), 'success');
                } else {
                    showAlert(data.message || 'Lokasi tidak ditemukan. Coba ketikkan nama tempat yang lebih spesifik.', 'warning');
                }
            })
            .catch(err => {
                console.error('Geocoding search failed:', err);
                showAlert('Kendala koneksi saat mencari alamat. Coba tandai langsung pada peta.', 'danger');
            })
            .finally(() => {
                if (btnSearch) {
                    btnSearch.disabled = false;
                    btnSearch.innerHTML = '<i class="bi bi-search me-1"></i> Cari';
                }
            });
        }

        if (btnSearch) {
            btnSearch.addEventListener('click', doSearchAddress);
        }

        if (searchInput) {
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    doSearchAddress();
                }
            });
        }

        // Browser Geolocation (GPS) Handler
        if (btnGps) {
            btnGps.addEventListener('click', function () {
                if (!navigator.geolocation) {
                    showAlert('Perangkat atau browser Anda tidak mendukung deteksi lokasi otomatis.', 'warning');
                    return;
                }

                btnGps.disabled = true;
                const originalHtml = btnGps.innerHTML;
                btnGps.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Mendeteksi...';

                navigator.geolocation.getCurrentPosition(
                    function (pos) {
                        btnGps.disabled = false;
                        btnGps.innerHTML = originalHtml;
                        const lat = pos.coords.latitude;
                        const lng = pos.coords.longitude;
                        const newPos = new google.maps.LatLng(lat, lng);
                        updateMarkerPosition(customerMarker, newPos);
                        map.panTo(newPos);
                        map.setZoom(16);
                        onLocationSelected(lat, lng, true);
                        showAlert('Titik lokasi berhasil disesuaikan dengan posisi GPS perangkat Anda.', 'success');
                    },
                    function (err) {
                        btnGps.disabled = false;
                        btnGps.innerHTML = originalHtml;
                        let msg = 'Gagal mendeteksi lokasi saat ini.';
                        if (err.code === err.PERMISSION_DENIED) {
                            msg = 'Izin akses lokasi ditolak oleh browser. Anda dapat menggeser pin peta atau mengetik alamat secara manual.';
                        } else if (err.code === err.POSITION_UNAVAILABLE) {
                            msg = 'Informasi lokasi GPS tidak tersedia pada perangkat Anda saat ini.';
                        } else if (err.code === err.TIMEOUT) {
                            msg = 'Waktu permintaan lokasi GPS habis. Silakan coba kembali.';
                        }
                        showAlert(msg, 'warning');
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            });
        }

        // Calculate Road Route via Google Routes API
        function calculateRoute(destLat, destLng) {
            if (!routeInfoBox) return;

            routeInfoBox.classList.remove('d-none');
            const distanceElem = document.getElementById('routeDistance');
            const durationElem = document.getElementById('routeDuration');
            const noteElem = document.getElementById('routeShippingNote');

            if (distanceElem) distanceElem.textContent = 'Menghitung...';
            if (durationElem) durationElem.textContent = '...';

            fetch(routeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    destination_latitude: destLat,
                    destination_longitude: destLng,
                    origin_latitude: baseLat,
                    origin_longitude: baseLng
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success || data.is_fallback) {
                    if (distanceElem) distanceElem.textContent = `${data.distance_km} km`;
                    if (durationElem) durationElem.textContent = data.duration_text || '-';
                    if (noteElem) {
                        noteElem.textContent = data.shipping_note || data.message || 'Konfirmasi akses armada pengiriman akan difinalkan saat survei teknis lokasi.';
                    }

                    // Render Polyline on Map
                    renderRoutePolyline(data.polyline, baseLat, baseLng, destLat, destLng);

                    // Update Checkout Pricing Summary secara transparan dan real-time
                    const subtotalElem = document.getElementById('checkoutSubtotal');
                    const shippingFeeElem = document.getElementById('checkoutShippingFeeText');
                    const grandTotalElem = document.getElementById('checkoutGrandTotal');

                    if (subtotalElem && shippingFeeElem && grandTotalElem) {
                        const subtotal = parseFloat(subtotalElem.dataset.subtotal || 0);
                        const fee = parseFloat(data.delivery_fee || 0);
                        const grandTotal = subtotal + fee;

                        if (fee > 0) {
                            shippingFeeElem.className = 'text-black fw-semibold tabular-nums';
                            shippingFeeElem.textContent = `+ Rp ${Math.round(fee).toLocaleString('id-ID')} (${data.extra_km} km ekstra)`;
                        } else {
                            shippingFeeElem.className = 'text-success fw-semibold tabular-nums';
                            shippingFeeElem.textContent = 'Termasuk (Gratis ≤ 15 km)';
                        }

                        grandTotalElem.textContent = `Rp ${Math.round(grandTotal).toLocaleString('id-ID')}`;
                    }
                } else {
                    if (distanceElem) distanceElem.textContent = '-';
                    if (durationElem) durationElem.textContent = '-';
                    if (noteElem) noteElem.textContent = data.message || 'Jarak rute belum dapat dikalkulasi secara otomatis.';
                }
            })
            .catch(err => {
                console.warn('Routes calculation failed:', err);
                if (distanceElem) distanceElem.textContent = '-';
                if (durationElem) durationElem.textContent = '-';
                if (noteElem) noteElem.textContent = 'Kendala menghitung rute rute darat Google Maps.';
            });
        }

        // Render Polyline on Google Maps
        function renderRoutePolyline(encodedPolyline, oLat, oLng, dLat, dLng) {
            if (routePolyline) {
                routePolyline.setMap(null);
                routePolyline = null;
            }

            let path = [];
            if (encodedPolyline && google.maps.geometry && google.maps.geometry.encoding) {
                path = google.maps.geometry.encoding.decodePath(encodedPolyline);
            } else {
                path = [
                    new google.maps.LatLng(oLat, oLng),
                    new google.maps.LatLng(dLat, dLng)
                ];
            }

            routePolyline = new google.maps.Polyline({
                path: path,
                geodesic: true,
                strokeColor: '#191919',
                strokeOpacity: 0.85,
                strokeWeight: 4,
                map: map
            });

            // Adjust bounds to show both Arsy Tenda office and Customer destination
            const bounds = new google.maps.LatLngBounds();
            bounds.extend(new google.maps.LatLng(oLat, oLng));
            bounds.extend(new google.maps.LatLng(dLat, dLng));
            map.fitBounds(bounds, { top: 50, bottom: 50, left: 50, right: 50 });
        }

        // Initial calculation on load
        updateCoordsFields(initialLat, initialLng);
        calculateRoute(initialLat, initialLng);
    };

    // Google Maps Script Loading Error Fallback
    window.gm_authFailure = function () {
        console.error('Google Maps API authentication failed.');
        const alertBox = document.getElementById('mapAlertBox');
        if (alertBox) {
            alertBox.className = 'alert alert-danger py-2 px-3 small rounded-3 d-flex align-items-center gap-2 mb-3';
            alertBox.innerHTML = `
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>Kunci API Google Maps tidak dapat diverifikasi oleh Google. Anda tetap dapat memasukkan alamat lokasi secara manual.</div>
            `;
            alertBox.classList.remove('d-none');
        }
    };
})();

