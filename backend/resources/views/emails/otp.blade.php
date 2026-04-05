<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>LeanOn Bot OTP</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f7; font-family: Arial, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                
                <table width="500" style="background:white; margin-top:40px; border-radius:10px; padding:30px;">
                    
                    <!-- Title -->
                    <tr>
                        <td align="center">
                            <h2 style="margin:0; color:#4CAF50;">LeanOn Bot</h2>
                            <p style="color:#888;">Your safe space for mental wellness</p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding-top:20px; text-align:center;">
                            
                            <h3 style="color:#333;">
                                {{ $type === 'register' ? 'Verify Your Account' : 'Reset Your Password' }}
                            </h3>

                            <p style="color:#555;">
                                Use the OTP below to continue:
                            </p>

                            <!-- OTP BOX -->
                            <div style="
                                font-size:32px;
                                font-weight:bold;
                                letter-spacing:8px;
                                background:#f1f1f1;
                                padding:15px;
                                display:inline-block;
                                border-radius:8px;
                                margin:20px 0;
                            ">
                                {{ $otp }}
                            </div>

                            <p style="color:#999; font-size:13px;">
                                This code will expire soon. Do not share it with anyone.
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="text-align:center; padding-top:20px;">
                            <p style="font-size:12px; color:#aaa;">
                                If you didn’t request this, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>