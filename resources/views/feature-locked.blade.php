<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feature Locked - ChatOn</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 48px;
            max-width: 500px;
            text-align: center;
        }
        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: #fff3cd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }
        h1 {
            font-size: 28px;
            color: #1a202c;
            margin-bottom: 16px;
        }
        p {
            color: #718096;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .badge {
            display: inline-block;
            background: #e2e8f0;
            color: #4a5568;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
        }
        .button {
            display: inline-block;
            background: #f5576c;
            color: white;
            padding: 12px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .button:hover {
            background: #e0485d;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 87, 108, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">⭐</div>
        <h1>{{ $feature }} Feature Locked</h1>
        <div class="badge">Current License: {{ ucfirst($license_type ?? 'Unknown') }}</div>
        <p>{{ $message }}</p>
        <p style="font-size: 14px;">Upgrade to Extended License to unlock this feature.</p>
        <a href="https://codecanyon.net" class="button">Upgrade License</a>
    </div>
</body>
</html>
