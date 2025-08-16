<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'category',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Get typed value based on the type field
     */
    public function getTypedValueAttribute()
    {
        switch ($this->type) {
            case 'boolean':
                return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $this->value;
            case 'json':
                return json_decode($this->value, true);
            case 'string':
            default:
                return $this->value;
        }
    }

    /**
     * Set typed value
     */
    public function setTypedValue($value): void
    {
        switch ($this->type) {
            case 'boolean':
                $this->value = $value ? 'true' : 'false';
                break;
            case 'integer':
                $this->value = (string) $value;
                break;
            case 'json':
                $this->value = json_encode($value);
                break;
            case 'string':
            default:
                $this->value = $value;
        }
    }

    /**
     * Scope to get settings by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get public settings
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Get setting value by key
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->typed_value : $default;
    }

    /**
     * Set setting value by key
     */
    public static function setValue(string $key, $value, string $type = 'string'): void
    {
        $setting = static::firstOrNew(['key' => $key]);
        $setting->type = $type;
        $setting->setTypedValue($value);
        $setting->save();
    }

    /**
     * Get all settings as key-value pairs
     */
    public static function getAllAsKeyValue(string $category = null): array
    {
        $query = static::query();

        if ($category) {
            $query->where('category', $category);
        }

        return $query->get()->pluck('typed_value', 'key')->toArray();
    }
}
