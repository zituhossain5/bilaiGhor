{{-- Contact form notification (App\Mail\ContactMail). Inline styles only — email clients drop <style>. All user input is escaped. --}}
@php
    $brand = '#e8861a';
    $telHref = preg_replace('/[^0-9+]/', '', $data['mobile']);
    $adminUrl = route('admin.contact.messages');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New contact message</title>
</head>
<body style="margin:0; padding:0; background:#f6f1ea; font-family:Arial, Helvetica, sans-serif; color:#3a1f0f;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f6f1ea; padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; background:#ffffff; border:1px solid #dccab2; border-radius:12px; overflow:hidden;">
                <tr>
                    <td style="background:{{ $brand }}; height:6px; line-height:6px; font-size:0;">&nbsp;</td>
                </tr>
                <tr>
                    <td style="padding:24px 28px 8px;">
                        <p style="margin:0 0 4px; font-size:13px; color:#7a6a5e; text-transform:uppercase; letter-spacing:0.5px;">BilaiGhor · Contact Us</p>
                        <h1 style="margin:0; font-size:22px; line-height:1.3; color:#3a1f0f;">New contact message</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:12px 28px 4px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:15px; line-height:1.5;">
                            <tr>
                                <td style="padding:8px 0; width:120px; color:#7a6a5e; vertical-align:top;">Full name</td>
                                <td style="padding:8px 0; font-weight:bold; vertical-align:top;">{{ $data['full_name'] }}</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0; color:#7a6a5e; vertical-align:top; border-top:1px solid #f0e6d8;">Mobile</td>
                                <td style="padding:8px 0; vertical-align:top; border-top:1px solid #f0e6d8;">
                                    <a href="tel:{{ $telHref }}" style="color:{{ $brand }}; text-decoration:none; font-weight:bold;">{{ $data['mobile'] }}</a>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0; color:#7a6a5e; vertical-align:top; border-top:1px solid #f0e6d8;">Email</td>
                                <td style="padding:8px 0; vertical-align:top; border-top:1px solid #f0e6d8;">
                                    @if($data['email'] !== '')
                                        <a href="mailto:{{ $data['email'] }}" style="color:{{ $brand }}; text-decoration:none;">{{ $data['email'] }}</a>
                                    @else
                                        <span style="color:#7a6a5e;">Not provided</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0; color:#7a6a5e; vertical-align:top; border-top:1px solid #f0e6d8;">Subject</td>
                                <td style="padding:8px 0; vertical-align:top; border-top:1px solid #f0e6d8;">
                                    @if($data['subject'] !== '')
                                        {{ $data['subject'] }}
                                    @else
                                        <span style="color:#7a6a5e;">No subject</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0; color:#7a6a5e; vertical-align:top; border-top:1px solid #f0e6d8;">Submitted</td>
                                <td style="padding:8px 0; vertical-align:top; border-top:1px solid #f0e6d8;">
                                    {{ $data['submitted_at']->format('d M Y, h:i A') }} <span style="color:#7a6a5e;">(Dhaka)</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:12px 28px 4px;">
                        <p style="margin:0 0 8px; font-size:13px; color:#7a6a5e; text-transform:uppercase; letter-spacing:0.5px;">Message</p>
                        <div style="padding:16px 18px; background:#fffdf8; border:1px solid #dccab2; border-left:4px solid {{ $brand }}; border-radius:8px; font-size:15px; line-height:1.6; color:#2a1505; word-break:break-word;">{!! nl2br(e($data['details'])) !!}</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 28px 28px;">
                        <a href="{{ $adminUrl }}" style="display:inline-block; padding:11px 22px; background:{{ $brand }}; color:#ffffff; font-weight:bold; font-size:14px; text-decoration:none; border-radius:24px;">Open Contact Messages</a>
                        @if($data['email'] !== '')
                            <p style="margin:14px 0 0; font-size:13px; color:#7a6a5e;">Tip: reply to this email to answer {{ $data['full_name'] }} directly.</p>
                        @endif
                    </td>
                </tr>
            </table>
            <p style="margin:14px 0 0; font-size:12px; color:#7a6a5e;">Sent from the BilaiGhor website contact form{{ $data['id'] ? ' · Message #' . $data['id'] : '' }}</p>
        </td>
    </tr>
</table>
</body>
</html>
