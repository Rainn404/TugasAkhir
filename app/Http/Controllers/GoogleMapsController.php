<?php

namespace App\Http\Controllers;

use App\Services\GoogleMapsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleMapsController extends Controller
{
    protected GoogleMapsService $googleMapsService;

    public function __construct(GoogleMapsService $googleMapsService)
    {
        $this->googleMapsService = $googleMapsService;
    }

    /**
     * Return public client-side map configuration.
     * Does NOT expose server key or private credentials.
     */
    public function config(): JsonResponse
    {
        return response()->json([
            'client_key' => $this->googleMapsService->getClientKey(),
            'base_location' => $this->googleMapsService->getBaseLocation(),
        ]);
    }

    /**
     * Geocode an address to latitude and longitude.
     */
    public function geocode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address' => ['required', 'string', 'max:500'],
        ]);

        $result = $this->googleMapsService->geocode($validated['address']);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Reverse geocode coordinates to a readable address.
     */
    public function reverseGeocode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $result = $this->googleMapsService->reverseGeocode(
            (float) $validated['latitude'],
            (float) $validated['longitude']
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Calculate route distance, duration, and road route from Arsy Tenda.
     */
    public function calculateRoute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'destination_latitude' => ['required', 'numeric', 'between:-90,90'],
            'destination_longitude' => ['required', 'numeric', 'between:-180,180'],
            'origin_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'origin_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $originLat = isset($validated['origin_latitude']) ? (float) $validated['origin_latitude'] : null;
        $originLng = isset($validated['origin_longitude']) ? (float) $validated['origin_longitude'] : null;

        $result = $this->googleMapsService->calculateRoute(
            (float) $validated['destination_latitude'],
            (float) $validated['destination_longitude'],
            $originLat,
            $originLng
        );

        return response()->json($result, 200);
    }
}

