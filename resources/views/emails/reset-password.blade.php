<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password</title>
</head>
<body style="margin:0; padding:0; background:#F7F9FD; font-family: 'Segoe UI', Tahoma, sans-serif; color:#0F1B3D;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="padding:40px 16px;">
    <tr>
      <td align="center">

        <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width:600px; background:#FFFFFF; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,.06);">

          <!-- Header -->
          <tr>
            <td style="background:linear-gradient(135deg,#1B4FD8,#3B82F6); padding:32px 40px; text-align:center;">
              <div style="display:inline-block; background:rgba(255,255,255,.15); padding:8px 16px; border-radius:8px; color:#FFFFFF; font-weight:800; font-size:14px; letter-spacing:1px;">
                IMA CREATIVE PRODUCTION
              </div>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:40px;">
              <h1 style="margin:0 0 16px; font-size:22px; font-weight:700; color:#0F1B3D;">
                Halo, {{ $userName }} 👋
              </h1>
              <p style="margin:0 0 16px; font-size:15px; line-height:1.7; color:#4B5675;">
                Kami menerima permintaan untuk mereset password akun Anda di IMA Creative Production.
              </p>
              <p style="margin:0 0 24px; font-size:15px; line-height:1.7; color:#4B5675;">
                Klik tombol di bawah ini untuk membuat password baru:
              </p>

              <!-- CTA Button -->
              <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto;">
                <tr>
                  <td style="background:#1B4FD8; border-radius:10px;">
                    <a href="{{ $resetUrl }}" target="_blank"
                       style="display:inline-block; padding:14px 32px; color:#FFFFFF; text-decoration:none; font-weight:700; font-size:15px;">
                      🔐 Reset Password Sekarang
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:32px 0 8px; font-size:13px; color:#6B7591;">
                Atau salin link berikut ke browser Anda:
              </p>
              <p style="margin:0 0 24px; padding:12px 16px; background:#F1F5FB; border-radius:8px; word-break:break-all; font-size:12px; color:#1B4FD8; font-family:monospace;">
                {{ $resetUrl }}
              </p>

              <!-- Warning -->
              <div style="background:#FEF3C7; border-left:4px solid #F59E0B; padding:14px 18px; border-radius:8px; margin:24px 0;">
                <p style="margin:0; font-size:13px; line-height:1.6; color:#92400E;">
                  ⏰ <strong>Link ini akan kedaluwarsa dalam {{ $expiryMins }} menit.</strong><br>
                  Jika Anda tidak meminta reset password, abaikan email ini — password Anda tidak akan berubah.
                </p>
              </div>

              <p style="margin:24px 0 0; font-size:13px; line-height:1.6; color:#6B7591;">
                Butuh bantuan? Hubungi tim kami di
                <a href="mailto:support@imacreative.id" style="color:#1B4FD8;">support@imacreative.id</a>
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#F7F9FD; padding:24px 40px; text-align:center; border-top:1px solid #E5EAF5;">
              <p style="margin:0; font-size:12px; color:#6B7591;">
                © {{ date('Y') }} PT. IMA Creative Production. All rights reserved.<br>
                Email ini dikirim otomatis — mohon jangan dibalas.
              </p>
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>
</body>
</html>
