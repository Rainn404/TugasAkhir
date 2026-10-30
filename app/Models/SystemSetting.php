<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, $value, ?string $description = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => (string) $value,
                'description' => $description,
            ]
        );
    }

    /**
     * Get URL for media setting, falling back to default asset.
     */
    public static function getImageUrl(string $key, string $defaultAsset): string
    {
        $val = static::get($key);
        if (!empty($val)) {
            if (str_starts_with($val, 'http://') || str_starts_with($val, 'https://')) {
                return $val;
            }
            if (Storage::disk('public')->exists($val)) {
                return Storage::disk('public')->url($val);
            }
            if (file_exists(public_path($val))) {
                return asset($val);
            }
        }
        return asset($defaultAsset);
    }
}

