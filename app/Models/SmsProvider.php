<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsProvider extends Model
{
    protected $fillable = [
        'name',
        'driver',
        'credentials',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credentials' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Obtenir le fournisseur actif.
     */
    public static function getActive(): ?self
    {
        return static::where('is_active', true)->first();
    }
}
