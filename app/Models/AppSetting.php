<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    // Values are stored JSON-encoded so arrays/booleans round-trip intact.
    public static function getValue(string $key, $default = null)
    {
        $row = static::where('key', $key)->first();
        if (!$row) {
            return $default;
        }

        $decoded = json_decode($row->value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
    }

    public static function setValue(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
    }
}
