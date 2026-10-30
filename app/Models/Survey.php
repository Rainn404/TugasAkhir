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
        'survey_result',
        'recommendations_json',
        'recommended_at',
        'recommendation_expires_at',
        'survey_fee',
        'survey_payment_status',
    ];

    protected $casts = [
        'event_date' => 'date',
        'survey_date' => 'date',
        'recommendations_json' => 'array',
        'recommended_at' => 'datetime',
        'recommendation_expires_at' => 'datetime',
        'survey_fee' => 'decimal:2',
    ];

    public function isRecommendationActive(): bool
    {
        if (empty($this->recommendations_json)) {
            return false;
        }

        if ($this->recommendation_expires_at && $this->recommendation_expires_at->isPast()) {
            return false;
        }

        return true;
    }

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

