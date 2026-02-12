<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Invalid - ChatOn</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: #fee;
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
            margin-bottom: 32px;
        }
        .button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .button:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔒</div>
        <h1>License Invalid</h1>
        <p>{{ $message }}</p>
        <a href="/install" class="button">Activate License</a>
    </div>
</body>
</html>
