<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Get setting value casted to appropriate type.
     */
    public function getCastedValue()
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($this->value) ? (float) $this->value : 0,
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Set setting value.
     */
    public function setValue($value): void
    {
        // Handle null/empty values
        if ($value === null || $value === '') {
            $this->value = '';
        } else {
            $this->value = match ($this->type) {
                'boolean' => $value ? '1' : '0',
                'json' => is_string($value) ? $value : json_encode($value),
                default => (string) $value,
            };
        }
        $this->save();
    }

    /**
     * Get setting by key.
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return $setting->getCastedValue();
    }

    /**
     * Set setting by key.
     */
    public static function set(string $key, $value, string $type = 'string', string $group = 'general', ?string $description = null): self
    {
        $setting = self::firstOrNew(['key' => $key]);
        $setting->type = $type;
        $setting->group = $group;
        $setting->description = $description;
        $setting->setValue($value);

        return $setting;
    }
}

