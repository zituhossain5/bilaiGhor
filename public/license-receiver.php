<?php
/**
 * LICENSE RECEIVER SCRIPT — Creative Design License System v2.0
 * =============================================================
 * এই ফাইলটি website-এর public/ folder-এ রাখুন (.env-এর পাশে নয়)।
 * License server (bmitltd.com) license তৈরি হলে এখানে POST করে।
 * Script push_token verify করে .env-এ LICENSE_KEY ও LICENSE_SIGNATURE লেখে।
 *
 * Security: HMAC SHA-256 token verification (timing-safe)
 * Usage:    https://yourclientdomain.com/license-receiver.php
 */

header('Content-Type: application/json');

// শুধু POST method accept
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

// Master server LicenseController::HMAC_SALT এর সাথে অবশ্যই মিলতে হবে
define('HMAC_SALT', 'your_secret_salt_key');

// JSON body বা POST data পড়া
$input = json_decode(file_get_contents('php://input'), true);
if (empty($input)) {
    $input = $_POST;
}

$licenseKey   = trim($input['license_key']   ?? '');
$signatureKey = trim($input['signature_key'] ?? '');
$expiryDate   = trim($input['expiry_date']   ?? '');
$pushToken    = trim($input['push_token']    ?? '');

// Required fields চেক — license_key, signature_key, push_token তিনটাই লাগবে
if (!$licenseKey || !$signatureKey || !$pushToken) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Required fields missing (license_key, signature_key, push_token).']);
    exit;
}

// Domain normalize: www. সরিয়ে lowercase
$domain = strtolower(preg_replace('#^www\.#i', '', $_SERVER['HTTP_HOST'] ?? ''));

// Push token verify: hash_hmac('sha256', domain|license_key, HMAC_SALT)
// hash_equals() — timing attack প্রতিরোধ করে
$expectedToken = hash_hmac('sha256', $domain . '|' . $licenseKey, HMAC_SALT);
if (!hash_equals($expectedToken, $pushToken)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid token.']);
    exit;
}

// .env path auto-detection — প্রথম writable path ব্যবহার হবে
$envPath = null;
foreach ([
    dirname(__DIR__) . '/.env',           // public/-এ script, root-এ .env (সবচেয়ে সাধারণ)
    dirname(dirname(__DIR__)) . '/.env',   // দুই স্তর ভেতরে থাকলে
    __DIR__ . '/../.env',                  // same-folder fallback
] as $path) {
    if (file_exists($path) && is_writable($path)) {
        $envPath = $path;
        break;
    }
}

if (!$envPath) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => '.env not found or not writable.']);
    exit;
}

// .env-এ key=value লেখা বা update করা
function writeEnvKey(string $envPath, string $key, string $value): void
{
    $content = file_get_contents($envPath);
    $line    = $key . '=' . $value;
    if (preg_match('/^' . preg_quote($key, '/') . '=/m', $content)) {
        $content = preg_replace('/^' . preg_quote($key, '/') . '=.*/m', $line, $content);
    } else {
        $content = rtrim($content) . "\n" . $line . "\n";
    }
    file_put_contents($envPath, $content);
}

// .env আপডেট
writeEnvKey($envPath, 'LICENSE_KEY',       $licenseKey);
writeEnvKey($envPath, 'LICENSE_SIGNATURE', $signatureKey);

// LICENSE_EXPIRY: lifetime হলে লেখা হবে না (doc অনুযায়ী)
if (!empty($expiryDate) && strtolower($expiryDate) !== 'lifetime') {
    writeEnvKey($envPath, 'LICENSE_EXPIRY', $expiryDate);
}

// OPcache reset — নতুন .env কাজ করতে
if (function_exists('opcache_reset')) {
    opcache_reset();
}

// পরের রিকোয়েস্টে master থেকে কী টানতে বাধ্য করবে
$stampDir = dirname($envPath) . '/storage/framework';
if (!is_dir($stampDir)) {
    @mkdir($stampDir, 0755, true);
}
@file_put_contents($stampDir . '/license_env_updated', (string) time());

// License cache clear — session validator cache সরানো
$cacheDir = dirname($envPath) . '/storage/framework/cache/data';
if (is_dir($cacheDir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        if ($file->isFile() && str_contains($file->getFilename(), '_session_validator')) {
            @unlink($file->getRealPath());
        }
    }
}

http_response_code(200);
echo json_encode([
    'status'  => 'ok',
    'message' => 'License saved successfully.',
    'domain'  => $domain,
]);
