<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    protected $table = 'configurations';
    protected $fillable = ['key', 'value', 'type', 'description'];

    /**
     * Get a configuration value by key
     */
    public static function get($key, $default = null)
    {
        $config = self::where('key', $key)->first();
        return $config ? $config->value : $default;
    }

    /**
     * Set a configuration value
     */
    public static function set($key, $value)
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Get all configurations as key-value array
     */
    public static function getAll()
    {
        return self::pluck('value', 'key')->toArray();
    }

    /**
     * Cast value based on type
     */
    public function getCastedValue()
    {
        return match ($this->type) {
            'boolean' => (bool) $this->value,
            'number' => (int) $this->value,
            'email' => (string) $this->value,
            default => (string) $this->value,
        };
    }
}
