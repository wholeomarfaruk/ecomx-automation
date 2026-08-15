<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup Successful</title>
</head>
<body style="margin:0;padding:0;background:#f0fdf4;font-family:Arial,Helvetica,sans-serif;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f0fdf4;padding:40px 20px;">
    <tr>
        <td align="center">

            <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">

                <!-- Success Header -->
                <tr>
                    <td align="center" style="padding:40px 30px 20px;">
                        <div style="width:56px;height:56px;line-height:56px;background:#dcfce7;border-radius:50%;margin:0 auto 20px;">
                            <span style="font-size:28px;color:#16a34a;">&#10003;</span>
                        </div>
                        <h1 style="margin:0 0 6px;font-size:22px;color:#111827;">
                            You're signed up, {name}!
                        </h1>
                        <p style="margin:0;font-size:14px;color:#6b7280;">
                            Your {app_name} account has been created successfully.
                        </p>
                    </td>
                </tr>

                <!-- Account Summary Card -->
                <tr>
                    <td style="padding:10px 30px 0;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;">
                            <tr>
                                <td style="padding:18px 20px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td style="font-size:12px;color:#9ca3af;padding-bottom:4px;">ACCOUNT EMAIL</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:14px;color:#111827;font-weight:bold;padding-bottom:12px;">{email}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:12px;color:#9ca3af;padding-bottom:4px;">SIGNED UP</td>
                                        </tr>
                                        <tr>
                                            <td style="font-size:14px;color:#111827;font-weight:bold;">{signup_date}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Welcome Copy -->
                <tr>
                    <td style="padding:28px 30px 8px;color:#374151;font-size:15px;line-height:1.7;">
                        <p style="margin:0 0 16px;">
                            Welcome aboard! We're excited to have you as part of {app_name}. Your account is ready — sign in whenever you're ready to get started.
                        </p>
                    </td>
                </tr>

                <!-- CTA Button -->
                <tr>
                    <td align="center" style="padding:8px 30px 40px;">
                        <a href="{login_url}" style="display:inline-block;padding:14px 32px;background:#16a34a;color:#ffffff;text-decoration:none;font-size:15px;font-weight:bold;border-radius:8px;">
                            Log In to Your Account
                        </a>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td align="center" style="padding:20px 30px;border-top:1px solid #f3f4f6;font-size:13px;color:#9ca3af;">
                        Didn't sign up for this? You can safely ignore this email.<br>
                        &copy; {year} {app_name}. All rights reserved.
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
