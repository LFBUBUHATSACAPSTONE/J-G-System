<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $purpose === 'account registration' ? 'Verify your email' : 'Password reset code' }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f2f4f7; color:#20242d; font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f2f4f7;">
        <tr>
            <td align="center" style="padding:36px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;">
                    <tr>
                        <td style="padding:0 0 18px;">
                            <p style="margin:0; color:#192235; font-size:19px; font-weight:700;">J&amp;G</p>
                            <p style="margin:4px 0 0; color:#697386; font-size:11px; letter-spacing:1.2px;">AUDIO LIGHTS AND SOUNDS</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px; background-color:#ffffff; border:1px solid #e3e7ed; border-radius:8px;">
                            <p style="margin:0 0 10px; color:#697386; font-size:11px; font-weight:700; letter-spacing:1.4px;">SECURITY VERIFICATION</p>
                            <h1 style="margin:0 0 12px; color:#192235; font-size:24px; line-height:1.3;">
                                {{ $purpose === 'account registration' ? 'Verify your email' : 'Reset your password' }}
                            </h1>
                            <p style="margin:0 0 24px; color:#535d6d; font-size:15px; line-height:1.6;">
                                {{ $purpose === 'account registration' ? 'Enter this code to finish creating your J&G account.' : 'Enter this code to continue resetting your password.' }}
                            </p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center" style="padding:18px 12px; background-color:#f5f6f8; border:1px solid #e6e9ee; border-radius:6px;">
                                        <p style="margin:0; color:#192235; font-size:30px; font-weight:700; letter-spacing:8px;">{{ $code }}</p>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:20px 0 0; color:#535d6d; font-size:13px; line-height:1.6;">
                                This code expires in <strong>10 minutes</strong> and can only be used once.
                            </p>
                            <p style="margin:12px 0 0; color:#697386; font-size:13px; line-height:1.6;">
                                If you didn’t request this, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 4px 0; color:#7a8391; font-size:12px; line-height:1.6;">
                            <p style="margin:0;">J&amp;G Audio Lights and Sounds</p>
                            <p style="margin:4px 0 0;">This is an automated security email. Please do not reply.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
