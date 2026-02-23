<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $fillable = [
        'prescription_id',
        'type',
        'destinataire',
        'message',
        'statut',
        'error_message',
        'sent_by',
    ];

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
