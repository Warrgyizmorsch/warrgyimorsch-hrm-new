{{-- Maintenance page (php artisan down). Self-contained: no app layout, no database, no assets build. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Warrgyizmorsch HRMS | Back shortly</title>
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico?v=2">
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon.png?v=2">
    <meta name="theme-color" content="#1d2a5b">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; }
        body { display: flex; align-items: center; justify-content: center; padding: 24px 16px; background: #f5f7fb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Inter, Roboto, Arial, sans-serif; color: #0f172a; }
        .card { width: 100%; max-width: 420px; background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 36px 28px; text-align: center; box-shadow: 0 10px 30px rgba(15, 23, 42, .06); }
        .logo { width: 100%; max-width: 260px; height: auto; margin: 0 auto 28px; display: block; }
        .badge { display: inline-block; padding: 5px 12px; border-radius: 999px; background: #eef1ff; color: #3858f9; font-size: 12px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; }
        h1 { font-size: 22px; margin: 16px 0 8px; }
        p { margin: 0; font-size: 15px; line-height: 1.6; color: #475569; }
        .retry { display: inline-block; margin-top: 24px; padding: 12px 22px; border-radius: 12px; background: #3858f9; color: #fff; text-decoration: none; font-weight: 600; font-size: 15px; }
    </style>
</head>
<body>
    <main class="card">
        <img class="logo" src="/assets/images/warr-logo.webp" alt="Warrgyizmorsch">
        <span class="badge">Scheduled maintenance</span>
        <h1>We'll be back shortly</h1>
        <p>The HRMS portal is being updated. Please try again in a few minutes.</p>
        <a class="retry" href="/">Try again</a>
    </main>
</body>
</html>
