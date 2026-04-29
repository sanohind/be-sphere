<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Your SPHERE Password</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono&display=swap');

    body { margin: 0; padding: 0; background-color: #f0ede8; font-family: 'DM Sans', 'Segoe UI', Arial, sans-serif; }

    .em-wrap { max-width: 560px; margin: 40px auto; background: #ffffff; border: 0.5px solid #e0ddd8; border-radius: 4px; overflow: hidden; }

    .em-head { padding: 24px 36px; background: #ffffff; border-bottom: 0.5px solid #e0ddd8; }

    .em-logo-text { font-size: 15px; font-weight: 600; color: #1a1a1a; letter-spacing: 0.05em; line-height: 1.2; }

    .em-logo-sub { font-size: 11px; color: #8a8782; letter-spacing: 0.04em; margin-top: 3px; line-height: 1.2; }

    .em-body { padding: 32px 36px; }

    .em-greeting { font-size: 17px; font-weight: 500; color: #1a1a1a; margin: 0 0 10px; }

    .em-text { font-size: 13.5px; color: #5c5a57; line-height: 1.75; margin: 0 0 24px; }

    .em-info { background: #f7f6f3; border-radius: 4px; padding: 16px 20px; margin-bottom: 24px; }

    .em-info-row { display: flex; align-items: baseline; margin-bottom: 10px; }

    .em-info-row:last-child { margin-bottom: 0; }

    .em-info-label { font-size: 10.5px; font-weight: 500; color: #9a9895; text-transform: uppercase; letter-spacing: 0.08em; width: 80px; flex-shrink: 0; padding-top: 1px; }

    .em-info-value { font-size: 13.5px; color: #1a1a1a; font-weight: 500; font-family: 'DM Mono', monospace; }

    .em-btn-wrap { margin: 28px 0; }

    .em-btn { display: inline-block; background: #c0392b; color: #ffffff !important; text-decoration: none; padding: 11px 28px; border-radius: 4px; font-size: 13.5px; font-weight: 500; letter-spacing: 0.02em; }

    .em-notice { display: flex; align-items: flex-start; gap: 10px; background: #fffbf0; border: 0.5px solid #e8d98a; border-radius: 4px; padding: 12px 16px; margin-bottom: 24px; }

    .em-notice p { margin: 0; font-size: 12.5px; color: #7a6a20; line-height: 1.6; }

    .em-divider { border: none; border-top: 0.5px solid #e0ddd8; margin: 24px 0; }

    .em-url { font-family: 'DM Mono', monospace; font-size: 11.5px; color: #5c7a9a; word-break: break-all; background: #f0f4f8; border-radius: 3px; padding: 8px 12px; margin-bottom: 20px; display: block; }

    .em-small { font-size: 12.5px; color: #8a8782; line-height: 1.7; margin: 0; }

    .em-footer { padding: 16px 36px 20px; border-top: 0.5px solid #e0ddd8; }

    .em-footer p { margin: 0; font-size: 11.5px; color: #a8a5a1; line-height: 1.7; }
  </style>
</head>
<body>
  <div class="em-wrap">

    <div class="em-head">
      <table cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="vertical-align:middle; padding-right:14px;">
            <img
              src="{{ $logoUrl }}"
              alt="SPHERE"
              style="display:block; height:40px; width:40px; border-radius:10px;"
            />
          </td>
          <td style="vertical-align:middle;">
            <div class="em-logo-text">SPHERE</div>
            <div class="em-logo-sub">Single Sign-On Portal</div>
          </td>
        </tr>
      </table>
    </div>

    <div class="em-body">
      <p class="em-greeting">Hello, {{ $user->name }}</p>
      <p class="em-text">
        A password reset request has been initiated for your SPHERE account by an administrator. Please click the button below to create a new password.
      </p>

      <div class="em-info">
        <div class="em-info-row">
          <span class="em-info-label">Username</span>
          <span class="em-info-value">{{ $user->username }}</span>
        </div>
        <div class="em-info-row">
          <span class="em-info-label">Email</span>
          <span class="em-info-value">{{ $user->email }}</span>
        </div>
      </div>

      <div class="em-btn-wrap">
        <a href="{{ $resetPasswordUrl }}" class="em-btn">Reset My Password</a>
      </div>

      <div class="em-notice">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" style="flex-shrink:0; margin-top:1px;">
          <path d="M7 1.5L12.5 11H1.5L7 1.5Z" stroke="#c9a820" stroke-width="1.2" stroke-linejoin="round"/>
          <path d="M7 5.5V8" stroke="#c9a820" stroke-width="1.2" stroke-linecap="round"/>
          <circle cx="7" cy="9.5" r="0.6" fill="#c9a820"/>
        </svg>
        <p>This link is only valid for <strong>24 hours</strong>. If you did not request this reset, please contact your system administrator immediately.</p>
      </div>

      <p class="em-text" style="margin-bottom: 8px;">
        If the button above does not work, copy and paste the following URL into your browser:
      </p>
      <span class="em-url">{{ $resetPasswordUrl }}</span>

      <hr class="em-divider">

      <p class="em-small">
        If you did not expect this email, please ignore it or contact your system administrator. Your password will not change unless you use the link above.
      </p>
    </div>

    <div class="em-footer">
      <p>© {{ date('Y') }} SPHERE - Sanoh Indonesia<br>This email was sent automatically, please do not reply.</p>
    </div>

  </div>
</body>
</html>
