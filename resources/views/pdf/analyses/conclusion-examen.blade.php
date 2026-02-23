{{-- resources/views/pdf/analyses/conclusion-examen.blade.php --}}
@php
    $analysisIds = [];
    $designationById = [];

    foreach ($examen->analyses as $analyse) {
        $analysisIds[] = $analyse->id;
        $designationById[$analyse->id] = $analyse->designation;

        if ($analyse->children && $analyse->children->isNotEmpty()) {
            foreach ($analyse->children as $child) {
                $analysisIds[] = $child->id;
                $designationById[$child->id] = $child->designation;

                if ($child->children && $child->children->isNotEmpty()) {
                    foreach ($child->children as $subChild) {
                        $analysisIds[] = $subChild->id;
                        $designationById[$subChild->id] = $subChild->designation;
                    }
                }
            }
        }
    }

    $analysisIds = array_values(array_unique($analysisIds));

    $notes = collect();
    if (!empty($analysisIds) && isset($prescription)) {
        $notes = \App\Models\AnalyseConclusionNote::where('prescription_id', $prescription->id)
            ->whereIn('analyse_id', $analysisIds)
            ->orderBy('created_at')
            ->get();
    }
@endphp

@if($notes->isNotEmpty())
    <div class="conclusion-examen">
        <div class="conclusion-examen-title">Notes :</div>
        <div class="conclusion-examen-content">
            @foreach($notes as $note)
                <div style="margin-bottom: 4px;">
                    <strong>{{ $designationById[$note->analyse_id] ?? 'Analyse' }}:</strong>
                    {{ optional($note->created_at)->format('d/m/Y H:i') }} —
                    {!! nl2br(e($note->note)) !!}
                </div>
            @endforeach
        </div>
    </div>
@endif
