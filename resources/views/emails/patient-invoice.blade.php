<!DOCTYPE html>
<html>
<head>
    <title>Votre Facture</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Bonjour {{ $prescription->patient->civilite }} {{ $prescription->patient->nom }},</h2>
    <p>Vous trouverez en pièce jointe la facture concernant votre prescription <strong>{{ $prescription->reference }}</strong> du {{ $prescription->created_at->format('d/m/Y') }}.</p>
    <p>Nous vous remercions de votre confiance.</p>
    <br>
    <p>Cordialement,</p>
    <p><strong>{{ \App\Models\Setting::getNomEntreprise() }}</strong></p>
</body>
</html>
