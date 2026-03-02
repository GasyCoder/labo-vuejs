<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'is_active',
        'plan_name',
        'subscription_price',
        'sms_quota',
        'email_quota',
        'sms_used_this_month',
        'email_used_this_month',
        'next_renewal_at',
    ];

    /**
     * Get the users that belong to this client.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the premium feature toggles for this client.
     */
    public function features()
    {
        return $this->hasMany(ClientFeature::class);
    }
}
