<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Étiquettes Prescriptions - {{ date('d/m/Y H:i') }}</title>
    <style>
        @page {
            margin: 15mm 10mm;
            size: A4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 0.9rem;
            line-height: 1.3;
            color: #000;
            background: white;
        }

        /* En-tête global */
        .page-header-global {
            text-align: left;
            margin-bottom: 8mm;
            padding-bottom: 3mm;
            border-bottom: 1px solid #000;
        }

        .lab-name-global {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 2mm;
        }

        /* Section patient */
        .patient-section {
            margin: 4mm 0;
            page-break-inside: avoid;
            border: 1pt solid #000;
            padding: 2mm;
        }

        /* En-tête patient */
        .patient-header {
            margin-bottom: 2mm;
            padding: 1.5mm;
            border: 0.5pt solid #000;
            font-weight: bold;
            font-size: 8pt;
            background: #f9f9f9;
        }

        /* Ligne d'étiquettes */
        .etiquettes-ligne-flex {
            width: 100%;
            margin-bottom: 1.5mm;
            page-break-inside: avoid;
        }

        /* Chaque étiquette */
        .etiquette-mini {
            display: inline-block;
            width: 37mm;
            height: 22mm;
            padding: 1mm;
            border: 0.5pt solid #000;
            background: white;
            vertical-align: top;
            font-size: 7pt;
            margin-right: 0.5mm;
            margin-bottom: 1mm;
            overflow: hidden;
            position: relative;
        }

        .etiquette-mini-header {
            text-align: center;
            font-weight: bold;
            font-size: 6pt;
            margin-bottom: 0.5mm;
            border-bottom: 0.5pt solid #000;
            padding-bottom: 0.5mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .barcode-mini {
            text-align: center;
            margin: 0.5mm 0;
            height: 7mm;
        }

        .barcode-image-mini {
            height: 6mm;
            width: auto;
            max-width: 100%;
            display: block;
            margin: 0 auto;
        }

        .barcode-ascii-mini {
            font-family: 'Courier New', monospace;
            font-size: 7pt;
            letter-spacing: 0.05em;
            text-align: center;
            font-weight: bold;
        }

        .patient-info-mini {
            font-size: 6pt;
            line-height: 1.1;
            text-align: center;
            border-top: 0.5pt solid #000;
            padding-top: 0.5mm;
            position: absolute;
            bottom: 1mm;
            left: 1mm;
            right: 1mm;
        }

        .patient-name-mini {
            font-weight: bold;
            font-size: 7pt;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Section sans tubes */
        .prescription-sans-tubes {
            margin: 4mm 0;
            padding: 3mm;
            border: 1px solid #000;
            text-align: center;
        }

        .sans-tubes-header {
            font-weight: bold;
            margin-bottom: 2mm;
            text-decoration: underline;
        }

        .sans-tubes-info {
            font-size: 0.75rem;
        }

        /* Section avec analyses */
        .prescription-avec-analyses {
            margin: 4mm 0;
            padding: 3mm;
            border: 1px solid #000;
            text-align: center;
        }

        .avec-analyses-header {
            font-weight: bold;
            margin-bottom: 2mm;
            text-decoration: underline;
        }

        .avec-analyses-info {
            font-size: 0.75rem;
            text-align: left;
        }

        .manual-page-break {
            page-break-before: always;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <!-- En-tête global -->
    <div class="page-header-global">
        <div class="lab-name-global">
            {{ strtoupper($laboratoire ?? 'LABORATOIRE CTB') }}
        </div>
        <div style="font-size: 0.8rem;">
            Étiquettes générées le {{ now()->format('d/m/Y à H:i') }}
        </div>
    </div>

    @php
        $sectionsParPage = 0;
        $maxSectionsParPage = 4;
    @endphp

    @foreach($prescriptions as $prescription)
        @php
            $patient = $prescription->patient;
            $tubes = $prescription->tubes;
            $nombreTubes = $tubes->count();

            // Civilité
            $civilite = '';
            if (isset($patient->civilite)) {
                switch (strtolower($patient->civilite)) {
                    case 'monsieur':
                    case 'mr':
                    case 'homme':
                        $civilite = 'M';
                        break;
                    case 'madame':
                    case 'mme':
                    case 'femme':
                        $civilite = 'F';
                        break;
                    case 'enfant masculin':
                    case 'garçon':
                    case 'enfant_m':
                        $civilite = 'EM';
                        break;
                    case 'enfant féminin':
                    case 'fille':
                    case 'enfant_f':
                        $civilite = 'EF';
                        break;
                    default:
                        $civilite = strtoupper(substr($patient->civilite, 0, 1));
                }
            }
        @endphp

        <!-- Saut de page si nécessaire -->
        @if($sectionsParPage >= $maxSectionsParPage)
            <div class="manual-page-break"></div>
            @php $sectionsParPage = 0; @endphp
        @endif

        @if($nombreTubes > 0)
            <!-- Section patient avec tubes -->
            <div class="patient-section">
                <!-- En-tête patient -->
                <div class="patient-header">
                    <div style="font-size: 0.9rem; margin-bottom: 1mm;">
                        {{ strtoupper($patient->nom ?? '') }} {{ ucfirst(strtolower($patient->prenom ?? '')) }}
                    </div>
                    <div style="font-size: 0.75rem;">
                        {{ $prescription->reference }} | 
                        Age: {{ $prescription->age ?? 'N/A' }} {{ $prescription->unite_age ?? '' }} |
                        {{ $prescription->created_at->format('d/m/Y') }}
                        @if(isset($prescription->prescripteur))
                            | Dr. {{ $prescription->prescripteur->nom }}
                        @endif
                    </div>
                </div>

                <!-- Étiquettes répétées 5 fois horizontalement -->
                @foreach($tubes as $tube)
                    <div class="etiquettes-ligne-flex">
                        @for($rep = 1; $rep <= 5; $rep++)
                            <div class="etiquette-mini">
                                <!-- En-tête étiquette -->
                                <div class="etiquette-mini-header">
                                    {{ strtoupper($tube->prelevement->denomination ?? 'TUBE') }}
                                    @if(isset($tube->prelevement->typeTubeRecommande))
                                        - {{ $tube->prelevement->typeTubeRecommande->code }}
                                    @endif
                                </div>

                                <!-- Code-barre -->
                                <div class="barcode-mini">
                                    @if(method_exists($tube, 'peutGenererCodeBarre') && $tube->peutGenererCodeBarre())
                                        @php
                                            try {
                                                $barcodeImage = method_exists($tube, 'genererCodeBarreImage') ? $tube->genererCodeBarreImage() : null;
                                            } catch (\Exception $e) {
                                                $barcodeImage = null;
                                            }
                                        @endphp

                                        @if(!empty($barcodeImage) && $barcodeImage !== 'data:image/png;base64,' && !str_contains($barcodeImage, 'error'))
                                            <img src="{{ $barcodeImage }}" alt="Code barre {{ $tube->code_barre }}" class="barcode-image-mini">
                                        @else
                                            <div class="barcode-ascii-mini">{{ $tube->code_barre ?? '||| || |||' }}</div>
                                        @endif
                                    @else
                                        <div class="barcode-ascii-mini">{{ $tube->code_barre ?? '||| || |||' }}</div>
                                    @endif
                                </div>

                                <!-- Infos patient -->
                                <div class="patient-info-mini">
                                    <div class="patient-name-mini">
                                        ({{ $civilite }}) {{ format_patient_name_short($patient->nom ?? '', $patient->prenom ?? '') }}
                                    </div>                                    <div style="font-weight: bold;">
                                        {{ $tube->code_barre }}
                                    </div>
                                    <div>
                                        {{ $prescription->age ?? 'N/A' }}{{ $prescription->unite_age ?? '' }} | {{ $prescription->created_at->format('d/m/y') }}
                                    </div>
                                </div>
                            </div>
                        @endfor
                    </div>
                @endforeach
            </div>

            @php $sectionsParPage++; @endphp

        @elseif(isset($prescription->analyses_data) && $prescription->analyses_data->count() > 0)
            <!-- Prescription avec analyses seulement -->
            <div class="patient-section">
                <!-- En-tête patient -->
                <div class="patient-header">
                    <div style="font-size: 0.9rem; margin-bottom: 1mm;">
                        {{ strtoupper($patient->nom ?? '') }} {{ ucfirst(strtolower($patient->prenom ?? '')) }} - ANALYSES
                        SEULEMENT
                    </div>
                    <div style="font-size: 0.75rem;">
                        {{ $prescription->reference }} | 
                        Age: {{ $prescription->age ?? 'N/A' }} {{ $prescription->unite_age ?? '' }} |
                        {{ $prescription->created_at->format('d/m/Y') }} | {{ $prescription->analyses_data->count() }}
                        analyse(s)
                        @if(isset($prescription->prescripteur))
                            | Dr. {{ $prescription->prescripteur->nom }}
                        @endif
                    </div>
                </div>

                <!-- 5 étiquettes identiques pour prescription avec analyses -->
                <div class="etiquettes-ligne-flex">
                    @for($rep = 1; $rep <= 5; $rep++)
                        <div class="etiquette-mini">
                            <!-- En-tête étiquette -->
                            <div class="etiquette-mini-header">
                                ANALYSES SEULEMENT
                            </div>

                            <!-- Info prescription -->
                            <div class="barcode-mini">
                                <div class="barcode-ascii-mini">{{ $prescription->reference }}</div>
                            </div>

                            <!-- Infos patient -->
                            <div class="patient-info-mini">
                                <div class="patient-name-mini">
                                    ({{ $civilite }}) {{ format_patient_name_short($patient->nom ?? '', $patient->prenom ?? '') }}
                                </div>
                                <div style="font-weight: bold;">
                                    {{ $prescription->reference }}
                                </div>
                                <div>
                                    {{ $prescription->age ?? 'N/A' }}{{ $prescription->unite_age ?? '' }} | {{ $prescription->created_at->format('d/m/y') }}
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>

            @php $sectionsParPage++; @endphp

        @else
            <!-- Prescription sans tubes ni analyses -->
            <div class="patient-section">
                <!-- En-tête patient -->
                <div class="patient-header">
                    <div style="font-size: 0.9rem; margin-bottom: 1mm;">
                        {{ strtoupper($patient->nom ?? '') }} {{ ucfirst(strtolower($patient->prenom ?? '')) }} - PRESCRIPTION
                        VIDE
                    </div>
                    <div style="font-size: 0.75rem;">
                        {{ $prescription->reference }} | 
                        Age: {{ $prescription->age ?? 'N/A' }} {{ $prescription->unite_age ?? '' }} |
                        {{ $prescription->created_at->format('d/m/Y') }}
                        @if(isset($prescription->prescripteur))
                            | Dr. {{ $prescription->prescripteur->nom }}
                        @endif
                    </div>
                </div>

                <!-- 5 étiquettes identiques pour prescription vide -->
                <div class="etiquettes-ligne-flex">
                    @for($rep = 1; $rep <= 5; $rep++)
                        <div class="etiquette-mini">
                            <!-- En-tête étiquette -->
                            <div class="etiquette-mini-header">
                                PRESCRIPTION VIDE
                            </div>

                            <!-- Info prescription -->
                            <div class="barcode-mini">
                                <div class="barcode-ascii-mini">{{ $prescription->reference }}</div>
                            </div>

                            <!-- Infos patient -->
                            <div class="patient-info-mini">
                                <div class="patient-name-mini">
                                    ({{ $civilite }}) {{ format_patient_name_short($patient->nom ?? '', $patient->prenom ?? '') }}
                                </div>
                                <div style="font-weight: bold;">
                                    {{ $prescription->reference }}
                                </div>
                                <div>
                                    {{ $prescription->age ?? 'N/A' }}{{ $prescription->unite_age ?? '' }} | {{ $prescription->created_at->format('d/m/y') }}
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>

            @php $sectionsParPage++; @endphp
        @endif
    @endforeach
</body>

</html>