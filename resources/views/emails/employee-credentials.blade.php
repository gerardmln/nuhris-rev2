<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>NU HRIS Credentials</title>
</head>
<body style="margin:0;padding:0;background-color:#eceef1;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#eceef1;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="560" style="max-width:560px;background-color:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #dbe0e6;">
                    <tr>
                        <td style="background-color:#00386f;padding:24px 28px;color:#ffffff;">
                            <div style="font-size:20px;font-weight:bold;letter-spacing:0.5px;">NU HRIS</div>
                            <div style="font-size:13px;color:#c7d7ea;margin-top:2px;">Human Resources Information System</div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px;">
                            <h1 style="margin:0 0 12px 0;font-size:22px;color:#1f2b5d;">
                                {{ $isResend ? 'Your credentials have been reset' : 'Welcome, '.$employee->first_name.'!' }}
                            </h1>
                            <p style="margin:0 0 16px 0;font-size:14px;line-height:1.55;color:#374151;">
                                @if ($isResend)
                                    Hi <strong>{{ $employee->full_name }}</strong>, the HR office has generated a new temporary password for your NU HRIS account. Please use the credentials below to sign in. Your old password will no longer work.
                                @else
                                    Hi <strong>{{ $employee->full_name }}</strong>, your NU HRIS account has been created. Please use the credentials below for your first sign-in and change the password once you're inside.
                                @endif
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:18px 0;background-color:#f4f7fb;border:1px solid #dbe0e6;border-radius:10px;">
                                <tr>
                                    <td style="padding:14px 18px;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Employee ID</td>
                                    <td style="padding:14px 18px;font-size:14px;color:#111827;font-family:'Courier New',monospace;text-align:right;">
                                        {{ $employee->employee_id }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;border-top:1px solid #e5e7eb;">Email / Username</td>
                                    <td style="padding:14px 18px;font-size:14px;color:#111827;font-family:'Courier New',monospace;text-align:right;border-top:1px solid #e5e7eb;">
                                        {{ $employee->email }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;border-top:1px solid #e5e7eb;">Temporary Password</td>
                                    <td style="padding:14px 18px;font-size:16px;color:#00386f;font-family:'Courier New',monospace;font-weight:bold;text-align:right;border-top:1px solid #e5e7eb;">
                                        {{ $temporaryPassword }}
                                    </td>
                                </tr>
                            </table>

                            <div style="margin:22px 0;text-align:center;">
                                <a href="{{ $loginUrl }}" style="display:inline-block;background-color:#00386f;color:#ffffff;text-decoration:none;padding:12px 26px;border-radius:8px;font-size:14px;font-weight:bold;">
                                    Sign in to NU HRIS
                                </a>
                            </div>

                            <p style="margin:14px 0 0 0;font-size:12px;line-height:1.55;color:#6b7280;">
                                For your security, please change this temporary password immediately after signing in. If you did not expect this email, please contact the HR office.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#f4f7fb;padding:16px 28px;font-size:11px;color:#6b7280;border-top:1px solid #dbe0e6;">
                            This is an automated message from NU HRIS. Please do not reply to this email.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
