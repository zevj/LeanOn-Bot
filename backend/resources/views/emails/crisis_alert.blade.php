<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Wellness Support — LeanOn Bot</title>
</head>
<body style="margin:0; padding:0; background-color:#f0f4f8; font-family: 'Segoe UI', Arial, sans-serif; -webkit-font-smoothing: antialiased;">

    <!-- Outer wrapper -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f4f8; padding: 40px 16px;">
        <tr>
            <td align="center">

                <!-- Card -->
                <table width="100%" style="max-width:580px; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">

                    <!-- ── HEADER BANNER ── -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0E6008 0%, #16a34a 100%); padding: 32px 40px 28px; text-align: center;">
                            <!-- Logo / Brand -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <div style="display:inline-block; background:rgba(255,255,255,0.15); border-radius:50px; padding: 6px 20px; margin-bottom:16px;">
                                            <span style="color:#ffffff; font-size:13px; font-weight:600; letter-spacing:0.5px;">🌿 LeanOn Bot</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <h1 style="margin:0; color:#ffffff; font-size:24px; font-weight:700; letter-spacing:-0.3px; line-height:1.3;">
                                            We're Here for You
                                        </h1>
                                        <p style="margin:8px 0 0; color:rgba(255,255,255,0.85); font-size:14px; line-height:1.5;">
                                            A message from the Guidance &amp; Wellness Team
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- ── BODY ── -->
                    <tr>
                        <td style="padding: 28px 40px 8px;">
                            <!-- Message paragraphs -->
                            @foreach($paragraphs as $para)
                                @if(trim($para) !== '')
                                    <p style="margin:0 0 16px; color:#374151; font-size:15px; line-height:1.7;">
                                        {{ $para }}
                                    </p>
                                @endif
                            @endforeach
                        </td>
                    </tr>

                    @if($appointmentFormatted)
                    <!-- ── APPOINTMENT CARD ── -->
                    <tr>
                        <td style="padding: 8px 40px 8px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; overflow:hidden;">
                                <tr>
                                    <td style="background:#0E6008; padding:10px 20px;">
                                        <span style="color:#ffffff; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em;">
                                            📅 Appointment Scheduled
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <p style="margin:0; color:#065f46; font-size:16px; font-weight:700; letter-spacing:-0.2px;">
                                            {{ $appointmentFormatted }}
                                        </p>
                                        <p style="margin:6px 0 0; color:#6b7280; font-size:13px; line-height:1.5;">
                                            Please make sure to attend this session. If you need to reschedule, contact the Guidance Office directly.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    <!-- ── DIVIDER ── -->
                    <tr>
                        <td style="padding: 24px 40px 0;">
                            <hr style="border:none; border-top:1px solid #e5e7eb; margin:0;" />
                        </td>
                    </tr>

                    <!-- ── RESOURCES SECTION ── -->
                    <tr>
                        <td style="padding: 20px 40px 8px;">
                            <p style="margin:0 0 12px; color:#6b7280; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.08em;">
                                Support Resources
                            </p>
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:8px 0; border-bottom:1px solid #f3f4f6;">
                                        <span style="color:#374151; font-size:13px;">🏫 &nbsp;<strong>Guidance Office</strong></span>
                                        <span style="color:#6b7280; font-size:13px; float:right;">Gordon College Campus</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0; border-bottom:1px solid #f3f4f6;">
                                        <span style="color:#374151; font-size:13px;">💬 &nbsp;<strong>LeanOn Bot Chat</strong></span>
                                        <span style="color:#6b7280; font-size:13px; float:right;">Available 24/7</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;">
                                        <span style="color:#374151; font-size:13px;">📞 &nbsp;<strong>Crisis Hotline</strong></span>
                                        <span style="color:#6b7280; font-size:13px; float:right;">1553 (NCMH)</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- ── FOOTER ── -->
                    <tr>
                        <td style="background:#f9fafb; border-top:1px solid #e5e7eb; padding:20px 40px; border-radius:0 0 16px 16px; text-align:center;">
                            <p style="margin:0 0 6px; color:#9ca3af; font-size:12px; line-height:1.6;">
                                This message was sent by the <strong style="color:#6b7280;">LeanOn Bot Wellness Support System</strong><br/>
                                Gordon College — Guidance &amp; Counseling Unit
                            </p>
                            <p style="margin:0; color:#d1d5db; font-size:11px;">
                                This is an automated message. Please do not reply directly to this email.
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- End Card -->

            </td>
        </tr>
    </table>

</body>
</html>
