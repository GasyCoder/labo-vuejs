<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PdfBranding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PdfBrandingController extends Controller
{
    public function index()
    {
        $branding = PdfBranding::active() ?? new PdfBranding([
            'exam_color' => '#d10000',
            'highlight_color' => '#d10000',
        ]);

        return Inertia::render('Admin/PdfBranding', [
            'branding' => $branding,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|image|max:2048',
            'exam_color' => 'required|string|size:7',
            'highlight_color' => 'required|string|size:7',
            'signature_image' => 'nullable|image|max:2048',
        ]);

        $branding = PdfBranding::active();
        
        if (!$branding) {
            // Check if we have a signature image if creating for the first time
            $request->validate([
                'signature_image' => 'required|image|max:2048',
            ]);
            $branding = new PdfBranding();
        }

        if ($request->hasFile('logo')) {
            if ($branding->logo_path) {
                Storage::disk('public')->delete($branding->logo_path);
            }
            $branding->logo_path = $request->file('logo')->store('branding', 'public');
        }

        if ($request->hasFile('signature_image')) {
            if ($branding->signature_image_path) {
                Storage::disk('public')->delete($branding->signature_image_path);
            }
            $branding->signature_image_path = $request->file('signature_image')->store('branding', 'public');
        }

        $branding->exam_color = $request->exam_color;
        $branding->highlight_color = $request->highlight_color;
        $branding->is_active = true;
        $branding->save();

        return redirect()->back()->with('success', 'Branding PDF mis à jour avec succès.');
    }
}
