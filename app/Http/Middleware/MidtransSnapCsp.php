<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * MidtransSnapCsp
 *
 * Menambahkan Content-Security-Policy header yang mengizinkan Midtrans Snap
 * untuk berjalan pada halaman order.
 *
 * Kebijakan CSP mencakup:
 * - CSP nonce yang dibuat secara aman per request untuk inline script / tag script aplikasi.
 * - Domain resmi Midtrans (Sandbox & Production) berdasarkan dokumentasi resmi:
 *   *.midtrans.com, *.veritrans.co.id, *.cloudfront.net, *.mixpanel.com, *.google-analytics.com.
 * - Payment channels: GoPay (*.gopayapi.com, gwk.gopayapi.com, pdk.gopayapi.com), Google Pay (pay.google.com),
 *   Shopee / AliCDN (*.alicdn.com, g.alicdn.com, o.alicdn.com), Xendit (snap.xendit.co).
 * - Aset aplikasi: Bootstrap 5 & Bootstrap Icons (cdn.jsdelivr.net), Leaflet (unpkg.com, *.tile.openstreetmap.org),
 *   Google Fonts (fonts.googleapis.com, fonts.gstatic.com).
 * - 'unsafe-inline' disertakan sebagai fallback backward-compatibility untuk komponen payment gateway.
 *
 * Referensi resmi: https://docs.midtrans.com/docs/snap-other-feature#content-security-policy-csp
 *
 * PENTING: Middleware ini HANYA diterapkan pada halaman order detail yang memuat Snap — bukan secara global.
 */
class MidtransSnapCsp
{
    public function handle(Request $request, Closure $next): Response
    {
        // Buat cryptographic nonce per request untuk script/elemen aman
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);
        view()->share('cspNonce', $nonce);

        $response = $next($request);

        // Jangan timpa CSP jika sudah diset oleh handler lain
        if ($response->headers->has('Content-Security-Policy')) {
            return $response;
        }

        $isProduction = filter_var(config('services.midtrans.is_production', false), FILTER_VALIDATE_BOOLEAN);

        if ($isProduction) {
            $snapDomain  = 'https://app.midtrans.com';
            $apiDomain   = 'https://api.midtrans.com';
            $assetDomain = 'https://snap-assets.midtrans.com';
        } else {
            $snapDomain  = 'https://app.sandbox.midtrans.com';
            $apiDomain   = 'https://api.sandbox.midtrans.com';
            $assetDomain = 'https://snap-assets.sandbox.midtrans.com';
        }

        // Domain yang dibutuhkan oleh Snap.js dan seluruh payment channel-nya
        $snapScriptSources = implode(' ', [
            "'self'",
            "'nonce-{$nonce}'",
            "'unsafe-inline'",
            "'unsafe-eval'",
            $snapDomain,
            $apiDomain,
            $assetDomain,
            'https://*.midtrans.com',
            'https://*.veritrans.co.id',
            'https://*.cloudfront.net',
            'https://pay.google.com',
            'https://gwk.gopayapi.com',
            'https://pdk.gopayapi.com',
            'https://*.gopayapi.com',
            'https://maps.googleapis.com',
            'https://*.googleapis.com',
            'https://maps.gstatic.com',
            'https://*.gstatic.com',
            'https://www.gstatic.com',
            'https://g.alicdn.com',
            'https://o.alicdn.com',
            'https://*.alicdn.com',
            'https://www.googletagmanager.com',
            'https://*.google-analytics.com',
            'https://*.mixpanel.com',
            'https://snap-sandbox.xendit.co',
            'https://snap.xendit.co',
            'https://cdn.jsdelivr.net',
            'https://unpkg.com',
            'https:',
        ]);

        $snapConnectSources = implode(' ', [
            "'self'",
            $snapDomain,
            $apiDomain,
            $assetDomain,
            'https://*.midtrans.com',
            'https://*.veritrans.co.id',
            'https://*.cloudfront.net',
            'https://pay.google.com',
            'https://gwk.gopayapi.com',
            'https://pdk.gopayapi.com',
            'https://*.gopayapi.com',
            'https://maps.googleapis.com',
            'https://*.googleapis.com',
            'https://*.mixpanel.com',
            'https://*.google-analytics.com',
            'https://snap-sandbox.xendit.co',
            'https://snap.xendit.co',
            'https://*.tile.openstreetmap.org',
            'https://cdn.jsdelivr.net',
            'https://unpkg.com',
            'https:',
            'data:',
            'blob:',
        ]);

        $snapFrameSources = implode(' ', [
            "'self'",
            $snapDomain,
            $assetDomain,
            'https://*.midtrans.com',
            'https://pay.google.com',
            'https://*.gopayapi.com',
            'https://snap-sandbox.xendit.co',
            'https://snap.xendit.co',
            'https:',
            'data:',
            'blob:',
        ]);

        $snapImgSources = implode(' ', [
            "'self'",
            'data:',
            'https://maps.googleapis.com',
            'https://*.googleapis.com',
            'https://maps.gstatic.com',
            'https://*.gstatic.com',
            'https://*.tile.openstreetmap.org',
            'https://*.midtrans.com',
            'https://snap-sandbox.xendit.co',
            'https://snap.xendit.co',
            'https://*.gopayapi.com',
            'https://pay.google.com',
            'https://unpkg.com',
            'https://cdn.jsdelivr.net',
            'https:',
        ]);

        $csp = implode('; ', [
            "default-src 'self' https: data: blob:",
            "script-src {$snapScriptSources}",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:",
            "frame-src {$snapFrameSources}",
            "connect-src {$snapConnectSources}",
            "img-src {$snapImgSources}",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
