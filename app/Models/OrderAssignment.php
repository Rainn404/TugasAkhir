<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'task_type',
        'scheduled_at',
        'status',
        'instructions',
        'worker_notes',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function getTaskTypeLabels(): array
    {
        return [
            'persiapan' => 'Persiapan Barang',
            'pengiriman' => 'Pengiriman Lokasi',
            'pemasangan' => 'Pemasangan Tenda / Alat',
            'pemeriksaan_kembali' => 'Pemeriksaan Pengembalian',
        ];
    }
}

