<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Support\AdminOrderNotification;
use App\Services\LicenseVerificationService;

class SuperLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('backEnd.auth.super-login', [
            'generalsetting' => GeneralSetting::where('status', 1)->first(),
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'ইমেল দিন।',
            'email.email'       => 'সঠিক ইমেল দিন।',
            'password.required' => 'পাসওয়ার্ড দিন।',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::guard('admin')->check()) {
            $existing = Auth::guard('admin')->user();
            if ($this->isVendorOrReseller($existing)) {
                Auth::guard('admin')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        if (! Auth::guard('admin')->attempt(
            ['email' => $request->input('email'), 'password' => $request->input('password')],
            $remember
        )) {
            return redirect()->route('super.login')
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => trans('auth.failed')]);
        }

        /** @var User $user */
        $user = Auth::guard('admin')->user();
        $request->session()->regenerate();

        if (! $this->allowsSuperPortal($user)) {
            Auth::guard('admin')->logout();
            return redirect()->route('super.login')
                ->withInput($request->only('email', 'remember'))
                ->with('error', 'এই লগইন শুধু সুপার অ্যাডমিন এর জন্য।');
        }

        AdminOrderNotification::flagAfterLogin();

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * লগইন ছাড়া: অ্যাপ ক্যাশ (optimize:clear) + storage/logs/*.log ইতিহাস খালি। সাফল্য পপআপ।
     */
    public function clearCaches(Request $request, LicenseVerificationService $license)
    {
        $ok       = false;
        $detail   = '';

        try {
            try {
                Artisan::call('optimize:clear');
            } catch (\Throwable $e) {
                Artisan::call('config:clear');
                Artisan::call('cache:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');
            }

            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }

            $license->clearAllLicenseCaches($request);
            $this->truncateStoredLogHistory();

            $ok     = true;
            $detail = trim((string) Artisan::output());

            if ($ok && !$license->isMasterDomain($request)) {
                $redirect = $license->enforceFreshLicenseCheck($request);
                if ($redirect !== null) {
                    return $redirect;
                }
            }
        } catch (\Throwable $e) {
            $ok     = false;
            $detail = $e->getMessage();
        }

        return response($this->clearCachesResultHtml($ok, $detail), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    private function clearCachesResultHtml(bool $success, string $detail): string
    {
        $title   = $success ? 'সফল' : 'সমস্যা';
        $message = $success
            ? 'ক্যাশ সফলভাবে মুছে ফেলা হয়েছে। লগ ইতিহাসও খালি করা হয়েছে।'
            : 'ক্যাশ ক্লিয়ার করতে ব্যর্থ। পারমিশন বা সার্ভার লগ চেক করুন।';
        $detailEsc = htmlspecialchars($detail !== '' ? $detail : '(no output)', ENT_QUOTES, 'UTF-8');
        $successAlertJs = $success
            ? 'try{alert(' . json_encode($message, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS) . ');}catch(e){}'
            : '';

        $boxBg = $success ? '#ecfdf5' : '#fef2f2';
        $accent = $success ? '#059669' : '#dc2626';
        $icon = $success ? '✓' : '!';

        return <<<HTML
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title} — Cache</title>
<style>
*{margin:0;box-sizing:border-box}
body{font-family:system-ui,-apple-system,BlinkMacSystemFont,sans-serif;background:#111827;color:#f9fafb;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
.overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;padding:20px;z-index:1;}
.popup{background:{$boxBg};color:#111827;border-radius:16px;max-width:420px;width:100%;padding:28px 24px;text-align:center;box-shadow:0 25px 50px rgba(0,0,0,.35);border:2px solid {$accent};position:relative;z-index:2;}
.badge{width:56px;height:56px;border-radius:50%;background:{$accent};color:#fff;font-size:28px;line-height:56px;margin:0 auto 16px;font-weight:700;}
h1{font-size:1.35rem;margin-bottom:12px;color:#111827;}
p{font-size:.95rem;line-height:1.55;color:#374151;margin-bottom:16px;}
pre{text-align:left;font-size:11px;background:#fff;border-radius:8px;padding:10px;margin-top:8px;overflow:auto;max-height:140px;color:#1f2937;border:1px solid #e5e7eb;}
button{border:0;border-radius:10px;padding:10px 22px;font-size:15px;font-weight:600;cursor:pointer;background:#1f2937;color:#fff;}
button:hover{opacity:.92;}
</style>
</head>
<body>
<div class="overlay" role="presentation">
  <div class="popup" role="dialog" aria-labelledby="dlg-title">
    <div class="badge">{$icon}</div>
    <h1 id="dlg-title">{$title}</h1>
    <p>{$message}</p>
    <pre>{$detailEsc}</pre>
    <button type="button" id="btn-ok">ঠিক আছে</button>
  </div>
</div>
<script>
(function(){
  {$successAlertJs}
  var b=document.getElementById('btn-ok');
  if(b){ b.addEventListener('click', function(){ window.close(); if(!window.closed) history.back(); }); }
})();
</script>
</body>
</html>
HTML;
    }

    private function truncateStoredLogHistory(): void
    {
        $logDir = storage_path('logs');
        if (!is_dir($logDir)) {
            return;
        }
        foreach (glob($logDir . DIRECTORY_SEPARATOR . '*.log') ?: [] as $path) {
            try {
                File::put($path, '');
            } catch (\Throwable $e) {
                @file_put_contents($path, '');
            }
        }
    }

    private function isVendorOrReseller(User $user): bool
    {
        if ($user->hasRole('vendor')) {
            return true;
        }
        if ($user->hasRole('reseller')) {
            return true;
        }
        if (isset($user->role) && in_array(strtolower((string) $user->role), ['vendor', 'reseller'], true)) {
            return true;
        }
        return false;
    }

    private function allowsSuperPortal(User $user): bool
    {
        if ((int) $user->id === 1) {
            return true;
        }
        if (isset($user->role) && strtolower((string) $user->role) === 'admin') {
            return true;
        }
        $roles = $user->getRoleNames()->map(fn ($r) => strtolower((string) $r))->toArray();

        return in_array('admin', $roles, true);
    }
}
