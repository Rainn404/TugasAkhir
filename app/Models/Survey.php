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
    ];

    protected $casts = [
        'event_date' => 'date',
        'survey_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
