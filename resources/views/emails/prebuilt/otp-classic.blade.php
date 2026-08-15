<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f4f4;padding:40px 20px;">
    <tr>
        <td align="center">

            <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;">

                <!-- Header -->
                <tr>
                    <td align="center" style="padding:30px 20px;border-bottom:1px solid #eeeeee;">
                        <h2 style="margin:0;font-size:24px;color:#111827;">
                            {app_name}
                        </h2>
                    </td>
                </tr>

                <!-- Content -->
                <tr>
                    <td style="padding:40px 30px;color:#374151;font-size:16px;line-height:1.6;">

                        <p style="margin:0 0 20px;">
                            Hello {name},
                        </p>

                        <p style="margin:0 0 25px;">
                            Use the following One-Time Password (OTP) to verify your account:
                        </p>

                        <div style="text-align:center;margin:30px 0;">
                            <span style="display:inline-block;padding:14px 28px;background:#f3f4f6;border:1px solid #d1d5db;border-radius:6px;font-size:32px;font-weight:bold;letter-spacing:8px;color:#111827;">
                                {code}
                            </span>
                        </div>

                        <p style="margin:0 0 10px;">
                            This code will expire in <strong>5 minutes</strong>.
                        </p>

                        <p style="margin:0;">
                            If you didn't request this code, you can safely ignore this email.
                        </p>

                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td align="center" style="padding:20px 30px;border-top:1px solid #eeeeee;font-size:13px;color:#6b7280;">
                        &copy; {year} {app_name}. All rights reserved.
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
