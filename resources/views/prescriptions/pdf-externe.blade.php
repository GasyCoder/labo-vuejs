<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Prescription Externe - {{ $prescription->reference }}</title>
    <style>
        @page { margin: 15mm 10mm; size: A4; }
        body { font-family: Arial, sans-serif; font-size: 10pt; line-height: 1.4; color: #333; }
        .header { display: table; width: 100%; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header-left { display: table-cell; width: 50%; vertical-align: bottom; }
        .header-right { display: table-cell; width: 50%; text-align: right; vertical-align: bottom; }
        .title { font-size: 18pt; font-weight: bold; color: #e67e22; text-transform: uppercase; margin-bottom: 5px; }
        .info-box { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px 0; }
        .label { font-weight: bold; color: #666; width: 120px; font-size: 9pt; }
        .value { font-weight: bold; color: #000; }
        .section-title { font-size: 12pt; font-weight: bold; border-left: 4px solid #e67e22; padding-left: 10px; margin: 25px 0 10px 0; text-transform: uppercase; }
        .analysis-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .analysis-table th { background: #34495e; color: white; padding: 10px; text-align: left; font-size: 9pt; text-transform: uppercase; }
        .analysis-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .code { font-family: monospace; font-weight: bold; color: #2980b9; }
        .total-row { background: #34495e; color: white; font-weight: bold; }
        .total-row td { padding: 12px 10px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8pt; color: #95a5a6; border-top: 1px solid #eee; padding-top: 10px; }
        .notes { margin-top: 20px; font-style: italic; color: #7f8c8d; font-size: 9pt; border: 1px dashed #bdc3c7; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    @php
        $settings = \App\Models\Setting::first();
        $nomEntreprise = $settings->nom_entreprise ?? 'LABORATOIRE CTB';
    @endphp

    <div class="header">
        <div class="header-left">
            <div class="title">Prescription Externe</div>
            <div><strong>Référence:</strong> {{ $prescription->reference }}</div>
        </div>
        <div class="header-right">
            <div><strong>Date:</strong> {{ $prescription->created_at->format('d/m/Y H:i') }}</div>
            <div><strong>Labo Source:</strong> {{ $nomEntreprise }}</div>
        </div>
    </div>

    <div class="info-box">
        <table class="info-table">
            <tr>
                <td class="label">Patient:</td>
                <td class="value">{{ strtoupper($prescription->patient->civilite ?? '') }} {{ strtoupper($prescription->patient->nom) }} {{ $prescription->patient->prenom }}</td>
                <td class="label">Âge / Sexe:</td>
                <td class="value">{{ $prescription->age }} {{ $prescription->unite_age ?? 'ans' }}</td>
            </tr>
            <tr>
                <td class="label">Prescripteur:</td>
                <td class="value">{{ $prescription->prescripteur->nom ?? 'N/A' }}</td>
                <td class="label">Téléphone:</td>
                <td class="value">{{ $prescription->patient->telephone ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Labo Traitant:</td>
                <td class="value" style="color: #e67e22;">{{ strtoupper($prescription->labo_autre_nom ?? 'Laboratoire Externe') }}</td>
                <td class="label">Poids:</td>
                <td class="value">{{ $prescription->poids ? $prescription->poids . ' Kg' : '-' }}</td>
            </tr>
        </table>
    </div>

    @if($prescription->renseignement_clinique)
        <div class="section-title">Renseignements Cliniques</div>
        <div class="notes">
            {{ $prescription->renseignement_clinique }}
        </div>
    @endif

    <div class="section-title">Analyses Demandées ({{ $prescription->analyses->count() }})</div>
    <table class="analysis-table">
        <thead>
            <tr>
                <th width="20%">Code</th>
                <th>Désignation de l'analyse</th>
                <th width="25%" style="text-align: right;">Prix Estimation</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($prescription->analyses as $analyse)
                @php $prix = (float)($analyse->pivot->prix ?? $analyse->prix); $total += $prix; @endphp
                <tr>
                    <td class="code">{{ $analyse->code }}</td>
                    <td>{{ $analyse->designation }}</td>
                    <td style="text-align: right; font-weight: bold;">{{ number_format($prix, 0, ',', ' ') }} Ar</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" style="text-align: right; text-transform: uppercase;">Montant Total Estimé</td>
                <td style="text-align: right;">{{ number_format($total, 0, ',', ' ') }} Ar</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 40px; text-align: right;">
        <div style="display: inline-block; text-align: center; width: 200px;">
            <p style="margin-bottom: 60px; font-weight: bold; text-decoration: underline;">Le Responsable</p>
            <p style="font-size: 9pt;">Cachet et Signature</p>
        </div>
    </div>

    <div class="footer">
        {{ $nomEntreprise }} - {{ $settings->adresse ?? '' }} - Tél: {{ $settings->telephone ?? '' }}
    </div>
</body>
</html>
