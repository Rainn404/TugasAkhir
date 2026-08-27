<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_name',
        'event_date',
        'address',
        'latitude',
        'longitude',
        'phone',
        'notes',
        'status',
        'survey_date',
        'admin_notes',
        'assigned_worker_id',
        'worker_instructions',
    ];

    protected $casts = [
        'event_date' => 'date',
        'survey_date' => 'date',
    ];

    /**
     * Pelanggan yang mengajukan survei.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pekerja / petugas lapangan yang ditugaskan melakukan survei.
     */
    public function assignedWorker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_worker_id');
    }
}

