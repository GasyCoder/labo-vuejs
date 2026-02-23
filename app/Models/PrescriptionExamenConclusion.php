<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescriptionExamenConclusion extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'examen_id',
        'conclusion',
        'created_by',
    ];

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function examen()
    {
        return $this->belongsTo(Examen::class);
    }
}
