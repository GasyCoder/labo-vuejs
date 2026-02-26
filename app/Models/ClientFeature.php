<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientFeature extends Model
{
    protected $fillable = [
        'client_id',
        'feature_key',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
