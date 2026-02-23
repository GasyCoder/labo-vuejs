<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Journal de Décaissement - Commissions Prescripteurs</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #d32f2f;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 11px;
            color: #666;
        }

        .period {
            text-align: center;
            font-weight: bold;
            margin: 15px 0;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #000;
            text-transform: uppercase;
            font-size: 10px;
        }

        td {
            padding: 8px;
            border-bottom: 0.5px solid #ddd;
            font-size: 10px;
            vertical-align: top;
        }

        .right {
            text-align: right;
        }

        .amount {
            font-weight: bold;
            color: #d32f2f;
        }

        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .total-label {
            text-align: right;
            text-transform: uppercase;
        }

        .total-amount {
            font-size: 12px;
            border-top: 1px solid #000;
        }

        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">LNB SITE MAITRE</div>
        <div class="company-info">
            Analyses Médicales<br>
            IMMEUBLE ARO<br>
            Tél: 0321145065
        </div>
    </div>

    <!-- Période -->
    <div class="period">
        JOURNAL DES DÉCAISSEMENTS (COMMISSIONS)
    </div>
    <div class="period">
        du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} au
        {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
    </div>

    @if($decaissements->count() > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">Date</th>
                    <th style="width: 25%;">Prescripteur</th>
                    <th style="width: 40%;">Dossier / Patient</th>
                    <th style="width: 20%;" class="right">Commission</th>
                </tr>
            </thead>
            <tbody>
                @foreach($decaissements as $paiement)
                    <tr>
                        <td>{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y H:i') : 'N/A' }}</td>
                        <td>{{ $paiement->prescription->prescripteur->nom_complet ?? 'N/A' }}</td>
                        <td>
                            <strong>{{ $paiement->prescription->patient->numero_dossier ?? 'N/A' }}</strong><br>
                            {{ $paiement->prescription->patient->nom_complet ?? 'N/A' }}
                        </td>
                        <td class="right amount">{{ number_format($paiement->commission_prescripteur, 2, '.', ' ') }} Ar.</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" class="total-label">Total Général</td>
                    <td class="right amount total-amount">{{ number_format($totalCommissions, 2, '.', ' ') }} Ar.</td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            Aucun décaissement enregistré durant cette période.
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        Rapport généré le {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>

</html>