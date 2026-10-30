<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredOrderHolds extends Command
{
    protected $signature = 'orders:release-expired-holds';
    protected $description = 'Melepas penguncian stok dan membatalkan pesanan pending payment yang melewati batas 5 jam tanpa pembayaran';

    public function handle(): int
    {
        $cutoff = now()->subHours(5);

        // Hanya batalkan pesanan pending yang status pembayarannya murni 'pending'
        // JANGAN batalkan jika status pembayaran 'unknown' atau 'conflict' (aturan bisnis: jangan melepas stok jika status belum dipastikan)
        $expiredOrders = Order::where('status', 'pending_payment')
            ->where('created_at', '<', $cutoff)
            ->where('payment_status', 'pending')
            ->get();

        $count = 0;
        foreach ($expiredOrders as $order) {
            $order->update([
                'status' => 'cancelled',
                'notes' => trim(($order->notes ? $order->notes . ' | ' : '') . '[Sistem: Dibatalkan otomatis karena batas penguncian stok 5 jam berakhir tanpa pembayaran]'),
            ]);
            $count++;
            Log::info("Pesanan {$order->order_number} dibatalkan otomatis karena melebihi batas waktu penguncian stok 5 jam.");
        }

        $this->info("Berhasil melepas {$count} pesanan yang kedaluwarsa.");
        return Command::SUCCESS;
    }
}

