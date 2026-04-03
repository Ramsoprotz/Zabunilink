<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $subject ?? 'ZabuniLink' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f4f4f5; color: #18181b; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .header { background: #10b981; padding: 32px 40px; text-align: center; }
        .header .logo { color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }
        .header .logo span { color: #d1fae5; }
        .body { padding: 40px; }
        .greeting { font-size: 18px; font-weight: 600; margin-bottom: 16px; }
        .text { font-size: 15px; color: #3f3f46; line-height: 1.6; margin-bottom: 16px; }
        .card { background: #f4f4f5; border-radius: 10px; padding: 20px 24px; margin: 24px 0; }
        .card-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
        .card-row:last-child { margin-bottom: 0; }
        .card-label { color: #71717a; }
        .card-value { font-weight: 600; color: #18181b; text-align: right; max-width: 60%; }
        .btn { display: inline-block; margin-top: 8px; padding: 13px 28px; background: #10b981; color: #ffffff; text-decoration: none; border-radius: 8px; font-size: 15px; font-weight: 600; }
        .btn-center { text-align: center; margin: 28px 0; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .badge-pending    { background: #fef3c7; color: #92400e; }
        .badge-shortlisted { background: #dbeafe; color: #1e40af; }
        .badge-awarded    { background: #d1fae5; color: #065f46; }
        .badge-rejected   { background: #fee2e2; color: #991b1b; }
        .badge-open       { background: #d1fae5; color: #065f46; }
        .divider { border: none; border-top: 1px solid #e4e4e7; margin: 28px 0; }
        .footer { padding: 24px 40px; background: #fafafa; border-top: 1px solid #e4e4e7; text-align: center; font-size: 13px; color: #a1a1aa; line-height: 1.6; }
        .footer a { color: #10b981; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <div class="logo">Zabuni<span>Link</span></div>
    </div>
    <div class="body">
        @yield('content')
    </div>
    <div class="footer">
        You are receiving this email because you have an account on ZabuniLink.<br />
        <a href="{{ rtrim(config('app.frontend_url', 'http://localhost:5173'), '/') }}">Visit ZabuniLink</a>
        &nbsp;·&nbsp;
        <a href="{{ rtrim(config('app.frontend_url', 'http://localhost:5173'), '/') }}/notifications">Manage Notifications</a>
    </div>
</div>
</body>
</html>
