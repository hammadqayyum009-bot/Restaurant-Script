<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0; padding:0; background:#f4efe6; font-family:'Segoe UI',Tahoma,Arial,sans-serif; color:#201512;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4efe6; padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 8px 26px rgba(61,15,22,0.10);">
                    <tr>
                        <td style="background:#3d0f16; padding:24px 28px;">
                            @if (config('site.logo'))
                                <img src="{{ url(config('site.logo')) }}" alt="{{ config('site.name') }}" style="max-height:44px; display:block;">
                            @else
                                <span style="font-size:20px; font-weight:700; color:#d9bd73; letter-spacing:0.02em;">{{ config('site.name') }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 28px 8px; font-size:15px; line-height:1.65; color:#3c2b25;">
                            {!! $body !!}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px 30px;">
                            <hr style="border:none; border-top:1px solid #eee0cd; margin:0 0 16px;">
                            <p style="margin:0; font-size:12.5px; line-height:1.6; color:#6b5a52;">
                                {{ config('site.name') }}<br>
                                {{ config('site.address') }}<br>
                                {{ config('site.phone') }} &middot; {{ config('site.email') }}
                            </p>
                        </td>
                    </tr>
                </table>
                <p style="margin:16px 0 0; font-size:11.5px; color:#a7968d;">&copy; {{ date('Y') }} {{ config('site.name') }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
