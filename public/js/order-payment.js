/**
 * order-payment.js
 * Mengelola interaksi UI untuk halaman detail pesanan:
 * - Membuka Midtrans Snap popup ketika tombol bayar diklik
 * - Konfirmasi dialog sebelum cancel transaksi
 * - Peta lokasi Google Maps
 *
 * Tidak ada inline script; file ini dimuat via <script defer>.
 * Snap.js Midtrans dimuat terlebih dahulu (tanpa defer) sehingga
 * window.snap tersedia saat IIFE ini dieksekusi.
 */
(() => {
    const statusElement = document.getElementById('payment-snap-status');
    const payButtons = Array.from(document.querySelectorAll('[data-pay-with-snap]'));

    function showStatus(message, type = 'info') {
        if (!statusElement) return;

        statusElement.textContent = message;
        statusElement.className = `alert alert-${type} py-2 small`;
    }

    function setPayButtonsDisabled(disabled) {
        payButtons.forEach((button) => {
            button.disabled = disabled;
        });
    }

    async function refreshServerStatus(button, fallbackMessage) {
        const statusUrl = button.dataset.paymentStatusUrl;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!statusUrl || !csrfToken) {
            setPayButtonsDisabled(false);
            showStatus(fallbackMessage, 'warning');
            return;
        }

        const originalHtml = button.innerHTML;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memeriksa Status...';
        setPayButtonsDisabled(true);
        try {
            const response = await fetch(statusUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
            const result = await response.json();
            if (!response.ok && response.status !== 202) {
                throw new Error('Payment status check failed.');
            }

            if (result.order_status === 'payment_conflict' || result.payment_status === 'conflict') {
                showStatus('Midtrans menemukan konflik pembayaran. Pesanan perlu direkonsiliasi oleh admin.', 'danger');
                window.setTimeout(() => window.location.reload(), 1500);
                return;
            }

            if (result.payment_status === 'paid') {
                showStatus('Pembayaran telah dikonfirmasi oleh server Midtrans.', 'success');
                window.setTimeout(() => window.location.reload(), 1500);
                return;
            }

            if (['cancelled', 'expired', 'failed'].includes(result.attempt_status)) {
                showStatus(`Status transaksi Midtrans: ${result.attempt_status}. Halaman akan diperbarui.`, 'warning');
                window.setTimeout(() => window.location.reload(), 1500);
                return;
            }

            if (result.attempt_status === 'pending') {
                showStatus(`${fallbackMessage} Pemeriksaan server masih menunjukkan pembayaran menunggu.`, 'info');
                return;
            }

            showStatus('Status pembayaran belum dapat dipastikan oleh server. Jangan membuat pembayaran baru dulu.', 'warning');
        } catch (error) {
            showStatus('Status pembayaran belum dapat diperiksa. Pesanan tetap belum terkonfirmasi; coba periksa kembali.', 'warning');
        } finally {
            button.disabled = false;
            button.removeAttribute('aria-busy');
            button.innerHTML = originalHtml;
            setPayButtonsDisabled(false);
        }
    }

    /**
     * Cek ketersediaan window.snap setelah semua script dimuat.
     * Snap.js menyuntikkan objek window.snap secara sinkron saat dimuat,
     * sehingga pengecekan ini cukup dilakukan sekali di sini.
     */
    if (payButtons.length && (!window.snap || typeof window.snap.pay !== 'function')) {
        showStatus(
            'Midtrans Snap gagal dimuat. Periksa koneksi internet dan pastikan halaman tidak dimuat dalam iframe, lalu muat ulang.',
            'danger'
        );
    }

    payButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const snapToken = button.dataset.snapToken;
            if (!snapToken) {
                showStatus('Token pembayaran tidak tersedia. Siapkan ulang pembayaran sebelum mencoba lagi.', 'danger');
                return;
            }

            if (!window.snap || typeof window.snap.pay !== 'function') {
                showStatus(
                    'Midtrans Snap tidak tersedia. Pastikan halaman tidak dalam iframe dan koneksi ke Sandbox aktif, lalu muat ulang.',
                    'danger'
                );
                return;
            }

            let callbackReceived = false;
            setPayButtonsDisabled(true);

            try {
                window.snap.pay(snapToken, {
                    onSuccess: () => {
                        callbackReceived = true;
                        refreshServerStatus(button,
                            'Midtrans menerima pembayaran. Status pesanan akan diperbarui setelah notifikasi backend terverifikasi.',
                        );
                    },
                    onPending: () => {
                        callbackReceived = true;
                        refreshServerStatus(button,
                            'Pembayaran masih menunggu. Status pesanan diperbarui setelah notifikasi Midtrans diterima backend.',
                        );
                    },
                    onError: () => {
                        callbackReceived = true;
                        refreshServerStatus(button,
                            'Pembayaran belum terverifikasi. Periksa instruksi pembayaran dan status pesanan sebelum mencoba lagi.',
                        );
                    },
                    onClose: () => {
                        if (!callbackReceived) {
                            refreshServerStatus(button,
                                'Popup ditutup. Ini tidak membatalkan transaksi; instruksi pembayaran yang sudah dibuat mungkin masih aktif.',
                            );
                        }
                    },
                });
            } catch (error) {
                refreshServerStatus(button, 'Midtrans Snap tidak dapat dibuka.');
            }
        });
    });

    document.querySelectorAll('[data-confirm-cancel-payment]').forEach((button) => {
        button.closest('form')?.addEventListener('submit', (event) => {
            if (!window.confirm('Coba batalkan transaksi pending ini? Jika Midtrans belum mengonfirmasi, transaksi yang sama tetap bisa dilanjutkan dan pembayaran baru tidak dibuat.')) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-check-payment-status]').forEach((button) => {
        button.addEventListener('click', () => {
            refreshServerStatus(button, 'Status transaksi masih belum dapat dipastikan.');
        });
    });

    // Handler jika autentikasi / billing Google Maps gagal
    window.gm_authFailure = function () {
        console.warn('Google Maps billing / auth failure detected on order page.');
        const mapElement = document.getElementById('orderMap');
        if (!mapElement) return;
        const lat = mapElement.dataset.latitude || '-3.7955';
        const lng = mapElement.dataset.longitude || '114.7712';
        const eventName = mapElement.dataset.eventName || 'Lokasi Acara';
        mapElement.innerHTML = `
            <div class="d-flex flex-column align-items-center justify-content-center text-center p-3 h-100" style="background-color: #FAF8F5; color: #555;">
                <i class="bi bi-geo-alt-fill text-warning fs-3 mb-1"></i>
                <strong class="text-black small mb-1">${eventName}</strong>
                <p class="small text-secondary mb-2" style="font-size: 0.75rem;">
                    Google Maps memerlukan Billing Account aktif di Google Cloud Console untuk menampilkan peta interaktif.
                </p>
                <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" rel="noopener noreferrer" class="btn btn-dark btn-sm rounded-pill px-3" style="font-size: 0.75rem;">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Titik di Google Maps
                </a>
            </div>
        `;
    };

    // Inisialisasi peta Google Maps jika elemen tersedia
    window.initOrderMap = function () {
        const mapElement = document.getElementById('orderMap');
        if (!mapElement || !window.google || !window.google.maps) return;

        const latitude = Number.parseFloat(mapElement.dataset.latitude);
        const longitude = Number.parseFloat(mapElement.dataset.longitude);
        const eventName = mapElement.dataset.eventName || 'Lokasi Acara';
        const address = mapElement.dataset.address || '';

        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;

        try {
            const map = new google.maps.Map(mapElement, {
                center: { lat: latitude, lng: longitude },
                zoom: 15,
                mapId: 'DEMO_MAP_ID',
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
                zoomControl: true,
            });

            const infoContent = `
                <div style="font-family: sans-serif; font-size: 13px; line-height: 1.4; padding: 4px; max-width: 240px;">
                    <strong style="color: #191919;">${eventName}</strong>
                    ${address ? `<div style="color: #555; font-size: 12px; margin-top: 4px;">${address}</div>` : ''}
                    <div style="color: #888; font-size: 11px; margin-top: 4px; font-family: monospace;">${latitude.toFixed(5)}, ${longitude.toFixed(5)}</div>
                </div>
            `;

            const infoWindow = new google.maps.InfoWindow({
                content: infoContent
            });

            if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
                const marker = new google.maps.marker.AdvancedMarkerElement({
                    position: { lat: latitude, lng: longitude },
                    map: map,
                    title: eventName,
                });
                marker.addListener('click', () => {
                    infoWindow.open({ anchor: marker, map: map });
                });
                infoWindow.open({ anchor: marker, map: map });
            } else {
                const marker = new google.maps.Marker({
                    position: { lat: latitude, lng: longitude },
                    map: map,
                    title: eventName,
                    animation: google.maps.Animation.DROP,
                });
                marker.addListener('click', () => {
                    infoWindow.open(map, marker);
                });
                infoWindow.open(map, marker);
            }
        } catch (err) {
            console.error('Google Maps init error:', err);
        }
    };

    if (window.google && window.google.maps) {
        window.initOrderMap();
    }
})();
