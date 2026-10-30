<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwalkan pelepasan penguncian stok pesanan yang melewati 5 jam tanpa pembayaran
Schedule::command('orders:release-expired-holds')->everyTenMinutes();

