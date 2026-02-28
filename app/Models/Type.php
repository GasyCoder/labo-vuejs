<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Type extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'libelle',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    protected $appends = ['meta'];

    /**
     * Get metadata from config/analyse_types.php
     */
    public function getMetaAttribute(): array
    {
        $allMeta = config('analyse_types', []);

        return $allMeta[$this->name] ?? [
            'label_metier' => $this->libelle ?: $this->name,
            'description' => '',
            'exemple' => '',
            'flags' => [
                'uses_ref' => true,
                'uses_suffix' => true,
                'is_title' => false,
                'is_choice' => false,
                'is_multi_choice' => false,
            ],
        ];
    }

    // Optionnel : scope pour les types actifs
    public function scopeActifs($query)
    {
        return $query->where('status', true);
    }

    // Relation avec les analyses
    public function analyses()
    {
        return $this->hasMany(Analyse::class);
    }
}
