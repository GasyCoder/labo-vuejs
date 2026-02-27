<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdfBranding extends Model
{
    use HasFactory;

    protected $fillable = [
        'logo_path',
        'exam_color',
        'highlight_color',
        'signature_image_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the active branding.
     */
    public static function active()
    {
        return self::where('is_active', true)->first();
    }
}
