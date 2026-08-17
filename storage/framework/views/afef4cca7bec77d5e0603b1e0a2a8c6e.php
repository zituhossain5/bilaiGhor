<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Super Admin | <?php echo e(optional($generalsetting)->name ?? config('app.name')); ?></title>
    <?php
        $fav = isset($generalsetting) && !empty($generalsetting->favicon) ? asset($generalsetting->favicon) : null;
        $logo = isset($generalsetting) && !empty($generalsetting->dark_logo) ? asset($generalsetting->dark_logo) : null;
    ?>
    <?php if($fav): ?>
        <link rel="shortcut icon" href="<?php echo e($fav); ?>" type="image/x-icon">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0c0f14;
            --panel: #151922;
            --border: #2a3344;
            --text: #e8ecf4;
            --muted: #8b96ab;
            --accent: #f59e0b;
            --accent-dim: #b45309;
            --danger: #f87171;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'DM Sans', system-ui, sans-serif;
            background: radial-gradient(1200px 800px at 80% -20%, rgba(245,158,11,0.08), transparent 50%),
                        radial-gradient(900px 600px at -10% 110%, rgba(56,189,248,0.06), transparent 45%),
                        var(--bg);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .shell {
            width: 100%;
            max-width: 420px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 12px;
        }
        .badge span {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 12px var(--accent);
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 8px;
            letter-spacing: -0.02em;
        }
        .sub {
            color: var(--muted);
            font-size: 0.9rem;
            margin-bottom: 28px;
            line-height: 1.45;
        }
        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.35);
        }
        .brand {
            margin-bottom: 22px;
        }
        .brand img {
            max-height: 36px;
            width: auto;
        }
        .brand-fallback {
            font-weight: 700;
            font-size: 1.05rem;
            color: var(--text);
        }
        label {
            display: block;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--muted);
            margin-bottom: 8px;
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text);
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.15s;
        }
        input:focus {
            border-color: var(--accent);
        }
        .field { margin-bottom: 18px; }
        .row-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 14px 0 22px;
            font-size: 0.88rem;
            color: var(--muted);
            user-select: none;
        }
        .row-check input { accent-color: var(--accent); }
        button[type="submit"] {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dim) 100%);
            color: #0c0f14;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: transform 0.1s, box-shadow 0.15s;
        }
        button[type="submit"]:hover {
            box-shadow: 0 10px 28px rgba(245,158,11,0.25);
            transform: translateY(-1px);
        }
        .alert {
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 0.875rem;
            margin-bottom: 18px;
            border: 1px solid transparent;
        }
        .alert-danger {
            background: rgba(248,113,113,0.12);
            border-color: rgba(248,113,113,0.35);
            color: var(--danger);
        }
        .error-text { margin-top: 6px; font-size: 0.8rem; color: var(--danger); }
    </style>
</head>
<body>
    <div class="shell">
        <div class="badge"><span></span> Super Admin</div>
        <h1><?php echo e(optional($generalsetting)->name ?? 'Control Panel'); ?></h1>
        <p class="sub">এই পেজটি সাধারণ অ্যাডমিন লগইন থেকে আলাদা। শুধু অনুমোদিত একাউন্ট দিয়ে প্রবেশ করুন।</p>

        <div class="card">
            <div class="brand">
                <?php if($logo): ?>
                    <img src="<?php echo e($logo); ?>" alt="">
                <?php else: ?>
                    <div class="brand-fallback"><?php echo e(optional($generalsetting)->name ?? config('app.name')); ?></div>
                <?php endif; ?>
            </div>

            <?php if(session('error')): ?>
                <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="alert alert-danger">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div><?php echo e($msg); ?></div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('super.login.submit')); ?>">
                <?php echo csrf_field(); ?>
                <div class="field">
                    <label for="email">ইমেল</label>
                    <input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus autocomplete="username"
                           placeholder="you@company.com">
                </div>
                <div class="field">
                    <label for="password">পাসওয়ার্ড</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                           placeholder="••••••••">
                </div>
                <label class="row-check">
                    <input type="checkbox" name="remember" value="1" <?php echo e(old('remember') ? 'checked' : ''); ?>>
                    আমাকে মনে রাখুন
                </label>
                <button type="submit">লগ ইন করুন</button>
            </form>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\laragon\www\bilaiGhor\resources\views\backEnd\auth\super-login.blade.php ENDPATH**/ ?>