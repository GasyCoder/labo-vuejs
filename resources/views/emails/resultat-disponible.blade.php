<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats disponibles</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f7; font-family: Arial, Helvetica, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f4f4f7; padding: 30px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#ffffff; border-radius:8px; overflow:hidden;">
                    {{-- Header --}}
                    <tr>
                        <td align="center" bgcolor="#dc2626" style="background-color:#dc2626; padding: 24px 30px;">
                            <h1 style="color:#ffffff; margin:0; font-size:20px; font-family:Arial,Helvetica,sans-serif;">Laboratoire Lareference</h1>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 30px;">
                            <div style="color:#333333; font-size:15px; line-height:1.7; font-family:Arial,Helvetica,sans-serif;">
                                @php
                                    $escaped = e($customMessage);
                                    $withLinks = preg_replace(
                                        '/(https?:\/\/[^\s<]+)/',
                                        '<a href="$1" style="color:#dc2626; text-decoration:underline; word-break:break-all;">$1</a>',
                                        $escaped
                                    );
                                @endphp
                                {!! nl2br($withLinks) !!}
                            </div>

                            @if($lienPdf)
                                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 30px 0;">
                                    <tr>
                                        <td align="center">
                                            <!--[if mso]>
                                            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $lienPdf }}" style="height:48px;v-text-anchor:middle;width:280px;" arcsize="12%" fillcolor="#dc2626">
                                            <w:anchorlock/>
                                            <center style="color:#ffffff;font-family:Arial,sans-serif;font-size:15px;font-weight:bold;">Télécharger mes résultats</center>
                                            </v:roundrect>
                                            <![endif]-->
                                            <!--[if !mso]><!-->
                                            <a href="{{ $lienPdf }}"
                                               target="_blank"
                                               style="display:inline-block; background:#dc2626; background-color:#dc2626; color:#ffffff !important; text-decoration:none; padding:14px 30px; border-radius:6px; font-size:15px; font-weight:bold; font-family:Arial,Helvetica,sans-serif; mso-hide:all;">
                                                Télécharger mes résultats
                                            </a>
                                            <!--<![endif]-->
                                        </td>
                                    </tr>
                                </table>
                                <p style="color:#888888; font-size:12px; text-align:center; margin-top:10px; font-family:Arial,Helvetica,sans-serif;">
                                    Ce lien est valable pendant 7 jours. Passé ce délai, veuillez contacter le laboratoire.
                                </p>
                            @endif
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td bgcolor="#f8fafc" style="background-color:#f8fafc; padding: 20px 30px; border-top: 1px solid #e2e8f0; text-align:center;">
                            <p style="color:#94a3b8; font-size:12px; margin:0; font-family:Arial,Helvetica,sans-serif;">
                                &copy; {{ date('Y') }} Laboratoire Lareference. Tous droits réservés.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
