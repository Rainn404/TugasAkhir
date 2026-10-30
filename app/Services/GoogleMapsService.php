<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleMapsService
{
    protected ?string $serverKey;
    protected ?string $clientKey;
    protected array $baseLocation;

    public function __construct()
    {
        $this->serverKey = config('services.google_maps.server_key');
        $this->clientKey = config('services.google_maps.client_key');
        $this->baseLocation = config('services.google_maps.base_location', [
            'name' => 'Kantor Arsy Tenda Pelaihari',
            'address' => 'Pelaihari, Kabupaten Tanah Laut, Kalimantan Selatan',
            'latitude' => -3.7955,
            'longitude' => 114.7712,
        ]);
    }

    /**
     * Get the configured base location of Arsy Tenda.
     */
    public function getBaseLocation(): array
    {
        return $this->baseLocation;
    }

    /**
     * Get the client API key for frontend Maps JavaScript API.
     */
    public function getClientKey(): ?string
    {
        return $this->clientKey;
    }

    /**
     * Geocode an address string to geographic coordinates.
     *
     * @param string $address
     * @return array
     */
    public function geocode(string $address): array
    {
        $address = trim($address);
        if (empty($address)) {
            return [
                'success' => false,
                'message' => 'Alamat pencarian tidak boleh kosong.',
            ];
        }

        if (empty($this->serverKey)) {
            return [
                'success' => false,
                'message' => 'Google Maps Server API Key belum dikonfigurasi di sistem.',
            ];
        }

        try {
            $response = Http::timeout(5)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $address,
                    'key' => $this->serverKey,
                    'language' => 'id',
                    'region' => 'id',
                ]);

            if (!$response->successful()) {
                Log::warning('Google Geocoding API HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Gagal menghubungi server Google Geocoding API. Silakan coba kembali.',
                ];
            }

            $data = $response->json();
            $status = $data['status'] ?? 'UNKNOWN_ERROR';

            if ($status === 'OK' && !empty($data['results'])) {
                $first = $data['results'][0];
                $location = $first['geometry']['location'] ?? [];

                return [
                    'success' => true,
                    'latitude' => (float) ($location['lat'] ?? 0),
                    'longitude' => (float) ($location['lng'] ?? 0),
                    'formatted_address' => $first['formatted_address'] ?? $address,
                    'place_id' => $first['place_id'] ?? null,
                ];
            }

            if ($status === 'ZERO_RESULTS') {
                return [
                    'success' => false,
                    'message' => 'Alamat tidak ditemukan di peta. Coba masukkan nama jalan, desa/kelurahan, atau patokan yang lebih spesifik.',
                ];
            }

            if ($status === 'REQUEST_DENIED') {
                Log::error('Google Geocoding API Request Denied', ['error' => $data['error_message'] ?? 'Unknown']);
                return [
                    'success' => false,
                    'message' => 'Permintaan Geocoding ditolak oleh Google. Pastikan Geocoding API aktif pada API key.',
                ];
            }

            return [
                'success' => false,
                'message' => $data['error_message'] ?? 'Gagal memproses geocoding alamat.',
            ];

        } catch (\Exception $e) {
            Log::error('Google Geocoding Exception: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kendala koneksi ke layanan geocoding: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Reverse geocode geographic coordinates to a readable address string.
     *
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    public function reverseGeocode(float $latitude, float $longitude): array
    {
        if (empty($this->serverKey)) {
            return [
                'success' => false,
                'message' => 'Google Maps Server API Key belum dikonfigurasi.',
            ];
        }

        try {
            $response = Http::timeout(5)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'latlng' => "{$latitude},{$longitude}",
                    'key' => $this->serverKey,
                    'language' => 'id',
                    'region' => 'id',
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Gagal menghubungi server Google Reverse Geocoding API.',
                ];
            }

            $data = $response->json();
            $status = $data['status'] ?? 'UNKNOWN_ERROR';

            if ($status === 'OK' && !empty($data['results'])) {
                $first = $data['results'][0];

                return [
                    'success' => true,
                    'formatted_address' => $first['formatted_address'] ?? '',
                    'place_id' => $first['place_id'] ?? null,
                    'address_components' => $first['address_components'] ?? [],
                ];
            }

            if ($status === 'ZERO_RESULTS') {
                return [
                    'success' => false,
                    'message' => 'Alamat tidak ditemukan untuk titik koordinat ini.',
                ];
            }

            return [
                'success' => false,
                'message' => $data['error_message'] ?? 'Gagal memproses reverse geocoding.',
            ];

        } catch (\Exception $e) {
            Log::error('Google Reverse Geocoding Exception: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Kendala koneksi reverse geocoding: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate route distance and duration using Google Routes API v2.
     *
     * @param float $destLat
     * @param float $destLng
     * @param float|null $originLat
     * @param float|null $originLng
     * @return array
     */
    public function calculateRoute(float $destLat, float $destLng, ?float $originLat = null, ?float $originLng = null): array
    {
        $originLat = $originLat ?? (float) $this->baseLocation['latitude'];
        $originLng = $originLng ?? (float) $this->baseLocation['longitude'];

        // If origin and destination are almost identical
        if (abs($originLat - $destLat) < 0.0001 && abs($originLng - $destLng) < 0.0001) {
            return [
                'success' => true,
                'distance_meters' => 0,
                'distance_km' => 0.0,
                'duration_seconds' => 0,
                'duration_text' => '0 menit',
                'origin' => ['latitude' => $originLat, 'longitude' => $originLng],
                'destination' => ['latitude' => $destLat, 'longitude' => $destLng],
                'polyline' => null,
                'is_fallback' => false,
                'note' => 'Lokasi berada di titik asal Arsy Tenda.',
            ];
        }

        if (empty($this->serverKey)) {
            $haversineKm = $this->calculateHaversineDistance($originLat, $originLng, $destLat, $destLng);
            return [
                'success' => false,
                'distance_meters' => (int) round($haversineKm * 1000),
                'distance_km' => $haversineKm,
                'duration_seconds' => (int) round($haversineKm * 120), // approx 30 km/h
                'duration_text' => round($haversineKm * 2) . ' menit (estimasi)',
                'origin' => ['latitude' => $originLat, 'longitude' => $originLng],
                'destination' => ['latitude' => $destLat, 'longitude' => $destLng],
                'polyline' => null,
                'is_fallback' => true,
                'message' => 'API Key Routes belum dikonfigurasi. Menggunakan kalkulasi jarak estimasi.',
            ];
        }

        try {
            $payload = [
                'origin' => [
                    'location' => [
                        'latLng' => [
                            'latitude' => $originLat,
                            'longitude' => $originLng,
                        ],
                    ],
                ],
                'destination' => [
                    'location' => [
                        'latLng' => [
                            'latitude' => $destLat,
                            'longitude' => $destLng,
                        ],
                    ],
                ],
                'travelMode' => 'DRIVE',
                'routingPreference' => 'TRAFFIC_AWARE',
                'computeAlternativeRoutes' => false,
                'languageCode' => 'id-ID',
            ];

            $response = Http::timeout(6)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Goog-Api-Key' => $this->serverKey,
                    'X-Goog-FieldMask' => 'routes.distanceMeters,routes.duration,routes.polyline.encodedPolyline,routes.description',
                ])
                ->post('https://routes.googleapis.com/directions/v2:computeRoutes', $payload);

            if (!$response->successful()) {
                Log::warning('Google Routes API HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $haversineKm = $this->calculateHaversineDistance($originLat, $originLng, $destLat, $destLng);
                return [
                    'success' => false,
                    'distance_meters' => (int) round($haversineKm * 1000),
                    'distance_km' => $haversineKm,
                    'duration_seconds' => (int) round($haversineKm * 120),
                    'duration_text' => round($haversineKm * 2) . ' menit (estimasi jarak lurus)',
                    'origin' => ['latitude' => $originLat, 'longitude' => $originLng],
                    'destination' => ['latitude' => $destLat, 'longitude' => $destLng],
                    'polyline' => null,
                    'is_fallback' => true,
                    'message' => 'Google Routes API merespons dengan kendala teknis. Estimasi jarak garis lurus ditampilkan.',
                ];
            }

            $data = $response->json();
            $routes = $data['routes'] ?? [];

            if (empty($routes)) {
                $haversineKm = $this->calculateHaversineDistance($originLat, $originLng, $destLat, $destLng);
                return [
                    'success' => false,
                    'distance_meters' => (int) round($haversineKm * 1000),
                    'distance_km' => $haversineKm,
                    'duration_seconds' => (int) round($haversineKm * 120),
                    'duration_text' => round($haversineKm * 2) . ' menit (estimasi jarak lurus)',
                    'origin' => ['latitude' => $originLat, 'longitude' => $originLng],
                    'destination' => ['latitude' => $destLat, 'longitude' => $destLng],
                    'polyline' => null,
                    'is_fallback' => true,
                    'message' => 'Tidak ditemukan rute darat ke lokasi tersebut oleh Google Routes API.',
                ];
            }

            $primaryRoute = $routes[0];
            $distanceMeters = (int) ($primaryRoute['distanceMeters'] ?? 0);
            $distanceKm = round($distanceMeters / 1000, 2);

            // Duration format from Routes API is e.g. "820s"
            $durationRaw = $primaryRoute['duration'] ?? '0s';
            $durationSeconds = (int) rtrim($durationRaw, 's');
            $durationMinutes = max(1, (int) round($durationSeconds / 60));
            $durationText = $durationMinutes >= 60
                ? floor($durationMinutes / 60) . ' jam ' . ($durationMinutes % 60) . ' menit'
                : $durationMinutes . ' menit';

            $polyline = $primaryRoute['polyline']['encodedPolyline'] ?? null;

            return [
                'success' => true,
                'distance_meters' => $distanceMeters,
                'distance_km' => $distanceKm,
                'duration_seconds' => $durationSeconds,
                'duration_text' => $durationText,
                'origin' => [
                    'name' => $this->baseLocation['name'],
                    'latitude' => $originLat,
                    'longitude' => $originLng,
                ],
                'destination' => [
                    'latitude' => $destLat,
                    'longitude' => $destLng,
                ],
                'polyline' => $polyline,
                'is_fallback' => false,
                'shipping_note' => $this->getShippingNote($distanceKm),
            ];

        } catch (\Exception $e) {
            Log::error('Google Routes API Exception: ' . $e->getMessage());

            $haversineKm = $this->calculateHaversineDistance($originLat, $originLng, $destLat, $destLng);
            return [
                'success' => false,
                'distance_meters' => (int) round($haversineKm * 1000),
                'distance_km' => $haversineKm,
                'duration_seconds' => (int) round($haversineKm * 120),
                'duration_text' => round($haversineKm * 2) . ' menit (estimasi)',
                'origin' => ['latitude' => $originLat, 'longitude' => $originLng],
                'destination' => ['latitude' => $destLat, 'longitude' => $destLng],
                'polyline' => null,
                'is_fallback' => true,
                'message' => 'Gagal menghubungkan Routes API: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Provide shipping policy notice based on distance.
     * Respects existing project policies (no arbitrary surcharge invented).
     */
    protected function getShippingNote(float $distanceKm): string
    {
        if ($distanceKm <= 15.0) {
            return 'Area Pelaihari kota (radius <= 15 km): Termasuk cakupan standar pengiriman & survei lapangan Arsy Tenda.';
        }

        return 'Lokasi di luar radius inti Pelaihari (> 15 km): Akses jalan armada dan biaya transportasi akan dikonfirmasi saat survei teknis lokasi.';
    }

    /**
     * Haversine formula to calculate great-circle distance between two points in km.
     */
    public function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 2);
    }
}

