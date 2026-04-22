{{-- Master Email Layout for all emails --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>{{ $title ?? config('app.name') }}</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background: #f5f8f7;
      font-family: 'Inter', 'Segoe UI', 'Helvetica Neue', sans-serif;
      color: #2d3436;
    }

    .email-wrapper {
      width: 100%;
      table-layout: fixed;
      background-color: #f5f8f7;
      padding: 30px 15px;
    }

    .email-content {
      max-width: 600px;
      margin: 0 auto;
      background-color: #ffffff;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 6px 18px rgba(0,0,0,0.06);
      text-align: center;
    }

    .logo-section {
      padding: 40px 20px 0;
    }

    .brand-name {
      font-size: 28px;
      font-weight: 600;
      color: #2d3436;
    }

    .content {
      padding: 20px 40px 40px;
    }

    .content h1 {
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 15px;
      color: #2d3436;
    }

    .content p {
      font-size: 15px;
      color: #636e72;
      margin-bottom: 25px;
      line-height: 1.5;
    }

    .verification-box {
      background: #f9fefb;
      border: 2px solid #e6f5ec;
      border-radius: 12px;
      padding: 25px;
      margin: 20px 0;
    }

    .code-label {
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #55a085;
      margin-bottom: 10px;
      font-weight: 500;
    }

    .otp {
      font-size: 34px;
      font-weight: 700;
      color: #2d3436;
      letter-spacing: 6px;
      font-family: 'Courier New', monospace;
    }

    .note {
      font-size: 12px;
      color: #55a085;
      margin-top: 12px;
      font-style: italic;
    }

    .footer {
      font-size: 12px;
      color: #b2bec3;
      text-align: center;
      padding: 20px;
      border-top: 1px solid #e8f0e8;
      background-color: #fafbfa;
    }

    @media screen and (max-width: 620px) {
      .email-content {
        width: 100% !important;
      }
      .content {
        padding: 20px;
      }
      .otp {
        font-size: 26px;
        letter-spacing: 4px;
      }
    }
  </style>
</head>
<body>
  <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td align="center">
        <table class="email-content" cellpadding="0" cellspacing="0" width="100%">
          <tr>
            <td class="logo-section">
              <div class="brand-name">{{ config('app.name') }}</div>
            </td>
          </tr>
          <tr>
            <td class="content">
              @yield('content')
            </td>
          </tr>
          <tr>
            <td class="footer">
              &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
              <span style="color:#aaa;">Crafted with care for your digital journey</span>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
