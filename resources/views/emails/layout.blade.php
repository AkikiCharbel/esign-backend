<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'DocuSign')</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 32px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
                    {{-- Accent stripe --}}
                    <tr>
                        <td style="background-color: #6366f1; height: 8px; line-height: 8px; font-size: 1px;">&nbsp;</td>
                    </tr>

                    {{-- Header --}}
                    <tr>
                        <td style="background-color: #ffffff; padding: 32px; text-align: center;">
                            <div style="font-size: 24px; font-weight: 600; color: #111827; margin: 0;">DocuSign</div>
                            <div style="font-size: 14px; color: #6b7280; margin-top: 4px;">Secure Document Signing</div>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="background-color: #ffffff; padding: 32px 40px; color: #374151; font-size: 15px; line-height: 1.6;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #f9fafb; padding: 24px; text-align: center;">
                            <div style="color: #9ca3af; font-size: 12px; margin-bottom: 4px;">This email was sent by DocuSign Clone</div>
                            <div style="color: #9ca3af; font-size: 12px; margin-bottom: 8px;">&copy; {{ date('Y') }} reviitale.com</div>
                            <div style="color: #9ca3af; font-size: 11px;">If you did not expect this email, you can safely ignore it.</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
