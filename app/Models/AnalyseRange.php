<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyseRange extends Model
{
    use HasFactory;

    protected $fillable = [
        'analyse_id',
        'context',
        'normal_min',
        'normal_max',
        'critical_min',
        'critical_max',
    ];

    public function analyse(): BelongsTo
    {
        return $this->belongsTo(Analyse::class);
    }
}
