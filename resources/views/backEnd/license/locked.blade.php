<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel Locked — License Required</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: linear-gradient(145deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(239, 68, 68, 0.35);
            border-radius: 16px;
            padding: 48px 40px;
            max-width: 520px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 24px;
            background: rgba(239, 68, 68, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
        }
        h1 {
            color: #f87171;
            font-size: 1.5rem;
            margin-bottom: 12px;
        }
        p {
            color: #94a3b8;
            line-height: 1.7;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        .domain {
            display: inline-block;
            margin: 16px 0;
            padding: 8px 14px;
            background: rgba(15, 23, 42, 0.8);
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.85rem;
            color: #cbd5e1;
        }
        .actions {
            margin-top: 28px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        a.btn {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.9rem;
            transition: opacity 0.2s;
        }
        a.btn:hover { opacity: 0.9; }
        a.btn-primary {
            background: #4f46e5;
            color: #fff;
        }
        a.btn-outline {
            border: 1px solid #475569;
            color: #cbd5e1;
        }
        .note {
            margin-top: 24px;
            font-size: 0.8rem;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">&#128274;</div>
        <h1>এডমিন প্যানেল লক</h1>
        <p>মাস্টার সার্ভার লাইসেন্স <strong>invalid</strong> রেসপন্স দিয়েছে। সার্ভার থেকে যাচাই সফল না হওয়া পর্যন্ত এডমিন প্যানেল বন্ধ থাকবে।</p>
        @if(!empty($serverMessage))
        <p style="color:#fca5a5;font-size:0.9rem;margin-top:12px;padding:12px;background:rgba(0,0,0,0.25);border-radius:8px;">
            {{ $serverMessage }}
        </p>
        @endif
        <p>এডমিন প্যানেল লক। ফ্রন্ট সাইট (শপ) স্বাভাবিকভাবে চলতে পারে।</p>
        <p class="note">লাইসেন্স নিন বা সক্রিয় করুন:
            <a href="https://www.bmitltd.com/license?domain={{ urlencode(request()->getHost()) }}" target="_blank" rel="noopener">Creative Design License</a>
        </p>
        <div class="domain">{{ request()->getHost() }}</div>
        <div class="actions">
            <a href="https://www.bmitltd.com/license?domain={{ urlencode(request()->getHost()) }}" class="btn btn-primary" target="_blank" rel="noopener">
                লাইসেন্স সক্রিয় করুন
            </a>
            <a href="{{ url('/') }}" class="btn btn-outline">হোমপেজে যান</a>
        </div>
        <p class="note">সমস্যা থাকলে BMITLTD সাপোর্টের সাথে যোগাযোগ করুন।</p>
    </div>
</body>
</html>
