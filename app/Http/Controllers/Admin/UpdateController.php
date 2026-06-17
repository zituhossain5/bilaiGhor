<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Support\CreativeDesignLicenseVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use ZipArchive;

class UpdateController extends Controller
{
    /**
     * Script Update API - Single endpoint (documentation format)
     */
    private const CHECK_UPDATE_URL = 'https://www.bmitltd.com/api/check-update';

    /**
     * Update files storage directory
     */
    private const UPDATE_STORAGE_PATH = 'updates';

    /**
     * Backup storage subdirectory
     */
    private const BACKUP_SUBDIR = 'backups';

    /**
     * Get update settings from database (fallback to env/config)
     */
    private function getUpdateSettings(): array
    {
        $setting = GeneralSetting::where('status', 1)->first();
        $apiUrl = ($setting && isset($setting->update_api_url) && trim($setting->update_api_url) !== '') ? trim($setting->update_api_url) : (env('UPDATE_API_URL') ? env('UPDATE_API_URL') : config('updater.api_url', 'https://www.bmitltd.com'));
        $scriptName = ($setting && isset($setting->update_script_name) && trim($setting->update_script_name) !== '') ? trim($setting->update_script_name) : (env('UPDATE_SCRIPT_NAME') ? env('UPDATE_SCRIPT_NAME') : config('updater.script_name', 'Ecommerce Pro'));
        $version = ($setting && isset($setting->app_version) && trim($setting->app_version) !== '') ? trim($setting->app_version) : (config('updater.current_version') ? config('updater.current_version') : config('app.version', '1.0.0'));
        return [
            'api_url' => $apiUrl,
            'script_name' => $scriptName,
            'current_version' => $version,
        ];
    }

    /**
     * Get check-update API URL
     */
    private function getCheckUpdateUrl(): string
    {
        $settings = $this->getUpdateSettings();
        return rtrim($settings['api_url'], '/') . '/api/check-update';
    }

    private function getDownloadUpdateUrl(): string
    {
        $settings = $this->getUpdateSettings();

        return rtrim($settings['api_url'], '/') . '/api/download-update';
    }

    /**
     * @return array{domain: string, license_key: string, signature: string, script_name: string, current_version: string, is_local: bool}
     */
    private function getLicenseCredentials(): array
    {
        $settings   = $this->getUpdateSettings();
        $domain     = CreativeDesignLicenseVerifier::resolveDomain();
        $scriptName = $settings['script_name'] ?: 'Ecommerce Pro';

        $payload = CreativeDesignLicenseVerifier::scriptUpdatePayload(
            $scriptName,
            $settings['current_version']
        );

        return array_merge($payload, [
            'is_local' => CreativeDesignLicenseVerifier::isLocalDomain($domain),
        ]);
    }

    private function postCheckUpdate(array $credentials): \Illuminate\Http\Client\Response
    {
        unset($credentials['is_local']);

        return CreativeDesignLicenseVerifier::postScriptUpdateApi(
            $this->getCheckUpdateUrl(),
            $credentials
        );
    }

    /**
     * @return array{body: string, content_type: string|null}
     */
    private function downloadUpdateZipFromApi(array $credentials, string $version): array
    {
        unset($credentials['is_local']);
        $credentials['version'] = $version;

        $response = CreativeDesignLicenseVerifier::postScriptUpdateApi(
            $this->getDownloadUpdateUrl(),
            $credentials,
            600
        );

        if (!$response->successful()) {
            $msg = $response->json('message') ?? ('HTTP ' . $response->status());
            throw new \RuntimeException('download-update ব্যর্থ: ' . $msg);
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if (str_contains($contentType, 'json')) {
            $json = $response->json();
            throw new \RuntimeException($json['message'] ?? 'download-update JSON error');
        }

        return [
            'body'         => $response->body(),
            'content_type' => $response->header('Content-Type'),
        ];
    }

    /**
     * Resolve download URL to actual file path.
     * API returns: .../storage/script-updates/FILE.zip
     * Actual path: .../project/storage/app/public/script-updates/FILE.zip
     */
    private function resolveDownloadUrl(string $url): string
    {
        $replacements = [
            '/storage/script-updates/' => '/project/storage/app/public/script-updates/',
            'bmitltd.com/storage/script-updates/' => 'bmitltd.com/project/storage/app/public/script-updates/',
        ];
        foreach ($replacements as $from => $to) {
            if (str_contains($url, $from)) {
                return str_replace($from, $to, $url);
            }
        }
        return $url;
    }

    /**
     * Show update management page
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        try {
            Log::info('Update page loaded', [
                'host' => request()->getHost(),
                'url' => request()->fullUrl(),
            ]);

            $licenseCheck = $this->verifyLicense();

            $settings = $this->getUpdateSettings();
            $currentVersion = $settings['current_version'];
            $scriptName = $settings['script_name'];
            if (empty($scriptName)) {
                $scriptName = 'Ecommerce Pro';
            }
            $domain = CreativeDesignLicenseVerifier::resolveDomain();
            $isLocal = CreativeDesignLicenseVerifier::isLocalDomain($domain);

            $licenseValid = $licenseCheck['valid'];
            $licenseMessage = $licenseCheck['message'];
            $licenseDomain = $licenseCheck['domain'] ?? $domain;
            $showSampleUpdate = $isLocal;

            if ($isLocal && $licenseValid) {
                $licenseMessage = 'Localhost (APP_URL: ' . $domain . ') — আপডেট চেক প্রোডাকশন ডোমেইনে করুন।';
            }

            Log::info('Update page data prepared', [
                'current_version' => $currentVersion,
                'script_name' => $scriptName,
                'license_valid' => $licenseValid,
                'license_message' => $licenseMessage,
                'license_domain' => $licenseDomain,
            ]);

            return view('backEnd.update.index', compact('licenseValid', 'licenseMessage', 'licenseDomain', 'currentVersion', 'scriptName', 'showSampleUpdate'));
        } catch (\Throwable $e) {
            Log::error('Update page error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify license validity
     * 
     * @return array Returns ['valid' => bool, 'message' => string, 'data' => array|null]
     */
    private function verifyLicense(): array
    {
        return CreativeDesignLicenseVerifier::verify(true);
    }

    /**
     * Check for available updates
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkUpdates(Request $request)
    {
        // Verify license first
        $licenseCheck = $this->verifyLicense();
        
        if (!$licenseCheck['valid']) {
            return response()->json([
                'status' => 'error',
                'message' => 'License validation failed: ' . $licenseCheck['message'],
                'updates_available' => false
            ], 403);
        }

        $currentVersion = $this->getUpdateSettings()['current_version'];
        
        try {
            $credentials = $this->getLicenseCredentials();
            
            // Check if running on localhost
            if (isset($credentials['is_local']) && $credentials['is_local']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Update check is not available on localhost/127.0.0.1. Please use a production domain with valid license.',
                    'updates_available' => false,
                    'current_version' => $currentVersion,
                    'latest_version' => $currentVersion,
                ], 400);
            }
            
            $credError = CreativeDesignLicenseVerifier::validateScriptUpdatePayload($credentials);
            if ($credError !== null) {
                return response()->json([
                    'status' => 'error',
                    'message' => $credError,
                    'updates_available' => false,
                    'current_version' => $currentVersion,
                    'latest_version' => $currentVersion,
                ], 400);
            }

            $apiUrl = $this->getCheckUpdateUrl();

            Log::info('Update check: API request', [
                'url' => $apiUrl,
                'domain' => $credentials['domain'],
                'script_name' => $credentials['script_name'],
                'current_version' => $credentials['current_version'],
                'has_signature' => !empty($credentials['signature']),
            ]);

            $response = $this->postCheckUpdate($credentials);

            if ($response->successful()) {
                $data = $response->json();
                $status = isset($data['status']) ? $data['status'] : 'up_to_date';

                Log::info('Update check: API response', [
                    'status' => $status,
                    'data' => $data,
                ]);
                
                // Script Update System format: update_available | up_to_date | invalid
                if ($status === 'update_available') {
                    return response()->json([
                        'status' => 'success',
                        'message' => isset($data['message']) ? $data['message'] : 'Update available',
                        'updates_available' => true,
                        'current_version' => $currentVersion,
                        'latest_version' => isset($data['version']) ? $data['version'] : $currentVersion,
                        'download_url' => isset($data['download_url']) ? $data['download_url'] : null,
                        'release_notes' => isset($data['release_notes']) ? $data['release_notes'] : '',
                        'update_info' => $data,
                    ]);
                }
                
                if ($status === 'invalid') {
                    $apiMsg = $data['message'] ?? 'লাইসেন্স কী বা ডোমেইন সঠিক নয়';
                    return response()->json([
                        'status' => 'error',
                        'message' => $apiMsg . ' (ডোমেইন: ' . $credentials['domain'] . ', স্ক্রিপ্ট: ' . $credentials['script_name'] . ')',
                        'updates_available' => false,
                        'current_version' => $currentVersion,
                        'latest_version' => $currentVersion,
                    ], 403);
                }
                
                // up_to_date or fallback
                return response()->json([
                    'status' => 'success',
                    'message' => isset($data['message']) ? $data['message'] : 'আপনার স্ক্রিপ্ট সর্বশেষ ভার্সনে আছে।',
                    'updates_available' => false,
                    'current_version' => $currentVersion,
                    'latest_version' => $currentVersion,
                    'update_info' => null,
                ]);
            } else {
                $statusCode = $response->status();
                $errorMessage = isset($response->json()['message']) ? $response->json('message') : $response->body();
                
                if ($statusCode === 404) {
                    $errorMessage = 'Update API endpoint not found. Please ensure the main site has /api/check-update configured.';
                }
                
                Log::error('Update check failed', [
                    'status' => $statusCode,
                    'url' => $apiUrl,
                    'response' => $response->body()
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to check for updates: ' . $errorMessage,
                    'updates_available' => false,
                    'current_version' => $currentVersion,
                    'latest_version' => $currentVersion,
                ], $statusCode);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Update check connection exception: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to connect to update server. Please check your internet connection and update server URL.',
                'updates_available' => false,
                'current_version' => $currentVersion,
                'latest_version' => $currentVersion,
            ], 503);
        } catch (\Exception $e) {
            Log::error('Update check exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to check for updates: ' . $e->getMessage(),
                'updates_available' => false,
                'current_version' => $currentVersion,
                'latest_version' => $currentVersion,
            ], 500);
        }
    }

    /**
     * Download update package
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function downloadUpdate(Request $request)
    {
        // Verify license first
        $licenseCheck = $this->verifyLicense();
        
        if (!$licenseCheck['valid']) {
            return response()->json([
                'status' => 'error',
                'message' => 'License validation failed: ' . $licenseCheck['message']
            ], 403);
        }

        $version = $request->input('version');
        $downloadUrl = $request->input('download_url');
        
        if (empty($version)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Version parameter is required'
            ], 400);
        }

        try {
            // Get download URL: use from request if passed, otherwise fetch from check-update API
            if (empty($downloadUrl)) {
                $credentials = $this->getLicenseCredentials();
                
                // Check if running on localhost
                if (isset($credentials['is_local']) && $credentials['is_local']) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Update download is not available on localhost/127.0.0.1. Please use a production domain with valid license.'
                    ], 400);
                }
                
                $credError = CreativeDesignLicenseVerifier::validateScriptUpdatePayload($credentials);
                if ($credError !== null) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $credError,
                    ], 400);
                }

                $response = $this->postCheckUpdate($credentials);

                if (!$response->successful()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to get download URL: ' . (isset($response->json()['message']) ? $response->json('message') : 'Server error')
                    ], $response->status());
                }

                $data = $response->json();
                if ((isset($data['status']) ? $data['status'] : '') !== 'update_available') {
                    return response()->json([
                        'status' => 'error',
                        'message' => isset($data['message']) ? $data['message'] : 'No update available for download'
                    ], 400);
                }

                $downloadUrl = isset($data['download_url']) ? $data['download_url'] : null;
            }

            $checksum = $request->input('checksum');
            $credentials = $this->getLicenseCredentials();

            $credError = CreativeDesignLicenseVerifier::validateScriptUpdatePayload($credentials);
            if ($credError !== null) {
                return response()->json(['status' => 'error', 'message' => $credError], 400);
            }

            $updateDir = storage_path('app/' . self::UPDATE_STORAGE_PATH);
            if (!File::exists($updateDir)) {
                File::makeDirectory($updateDir, 0755, true);
            }

            $fileName = 'update-' . $version . '-' . time() . '.zip';
            $filePath = $updateDir . '/' . $fileName;

            $zipBody = null;

            try {
                Log::info('Downloading update via POST download-update API', ['version' => $version]);
                $zipBody = $this->downloadUpdateZipFromApi($credentials, $version)['body'];
            } catch (\Throwable $apiDl) {
                Log::warning('POST download-update failed, trying URL fallback', [
                    'error' => $apiDl->getMessage(),
                    'download_url' => $downloadUrl,
                ]);

                if (empty($downloadUrl)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $apiDl->getMessage(),
                    ], 500);
                }

                $downloadUrl = $this->resolveDownloadUrl($downloadUrl);
                $fileResponse = Http::withoutVerifying()
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0',
                        'Accept'     => 'application/zip,*/*',
                    ])
                    ->timeout(300)
                    ->get($downloadUrl);

                if (!$fileResponse->successful()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to download update file (HTTP ' . $fileResponse->status() . ')',
                    ], 500);
                }

                $zipBody = $fileResponse->body();
            }

            File::put($filePath, $zipBody);

            // Step 4: Verify file integrity (if checksum provided)
            if (!empty($checksum)) {
                $fileChecksum = md5_file($filePath);
                if ($fileChecksum !== $checksum) {
                    File::delete($filePath);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'File integrity check failed. Download may be corrupted.'
                    ], 400);
                }
            }

            // Step 5: Store download info in cache for installation
            Cache::put('update_download_' . $version, [
                'file_path' => $filePath,
                'version' => $version,
                'downloaded_at' => now()->toDateTimeString(),
                'checksum' => $checksum,
            ], now()->addHours(24));

            Log::info('Update downloaded successfully', [
                'version' => $version,
                'file_path' => $filePath,
                'file_size' => File::size($filePath)
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Update downloaded successfully',
                'version' => $version,
                'file_size' => File::size($filePath),
                'file_path' => $filePath,
            ]);

        } catch (\Exception $e) {
            Log::error('Update download exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download update: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create backup on demand (code zip + database) - works without update
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createBackup(Request $request)
    {
        $licenseCheck = $this->verifyLicense();
        if (!$licenseCheck['valid']) {
            return response()->json(['status' => 'error', 'message' => 'License invalid'], 403);
        }

        try {
            $prefix = 'manual-' . date('Y-m-d-His');
            $codePath = $this->createSiteCodeBackup($prefix);
            $dbPath = $this->createDatabaseBackup($prefix);

            return response()->json([
                'status' => 'success',
                'message' => 'ব্যাকআপ সফলভাবে তৈরি হয়েছে।',
                'code_backup' => $codePath ? basename($codePath) : null,
                'db_backup' => $dbPath ? basename($dbPath) : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Create backup error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * List available backup files (code zip + database sql)
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function listBackups()
    {
        $licenseCheck = $this->verifyLicense();
        if (!$licenseCheck['valid']) {
            return request()->expectsJson()
                ? response()->json(['status' => 'error', 'message' => 'License invalid'], 403)
                : redirect()->route('admin.updates.index')->with('error', 'License invalid');
        }

        $backupDir = storage_path('app/' . self::UPDATE_STORAGE_PATH . '/' . self::BACKUP_SUBDIR);
        $backups = [];

        if (File::exists($backupDir)) {
            $files = File::files($backupDir);
            foreach ($files as $file) {
                $name = $file->getFilename();
                $type = str_ends_with($name, '-code.zip') ? 'code' : (str_ends_with($name, '-database.sql') ? 'database' : 'other');
                $backups[] = [
                    'name' => $name,
                    'type' => $type,
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                ];
            }
            usort($backups, fn ($a, $b) => $b['modified'] <=> $a['modified']);
        }

        return response()->json(['status' => 'success', 'backups' => $backups]);
    }

    /**
     * Download a backup file
     *
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     */
    public function downloadBackup(string $filename)
    {
        $licenseCheck = $this->verifyLicense();
        if (!$licenseCheck['valid']) {
            abort(403, 'License invalid');
        }

        $filename = basename($filename);
        if (str_contains($filename, '..') || !preg_match('/^[a-zA-Z0-9_\-\.]+\.(zip|sql)$/', $filename)) {
            abort(404, 'Invalid file');
        }

        $path = storage_path('app/' . self::UPDATE_STORAGE_PATH . '/' . self::BACKUP_SUBDIR . '/' . $filename);
        if (!File::exists($path) || !File::isFile($path)) {
            abort(404, 'File not found');
        }

        return response()->download($path, $filename, [
            'Content-Type' => str_ends_with($filename, '.zip') ? 'application/zip' : 'application/sql',
        ]);
    }

    /**
     * Get update information
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUpdateInfo(Request $request)
    {
        // Verify license first
        $licenseCheck = $this->verifyLicense();
        
        if (!$licenseCheck['valid']) {
            return response()->json([
                'status' => 'error',
                'message' => 'License validation failed: ' . $licenseCheck['message'],
                'license_valid' => false
            ], 403);
        }

        $version = $request->input('version');
        
        if (empty($version)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Version parameter is required'
            ], 400);
        }

        try {
            $credentials = $this->getLicenseCredentials();
            
            // Check if running on localhost
            if (isset($credentials['is_local']) && $credentials['is_local']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Update check is not available on localhost/127.0.0.1. Please use a production domain with valid license.',
                    'license_valid' => false
                ], 400);
            }
            
            $credError = CreativeDesignLicenseVerifier::validateScriptUpdatePayload($credentials);
            if ($credError !== null) {
                return response()->json([
                    'status' => 'error',
                    'message' => $credError,
                    'license_valid' => false,
                ], 400);
            }

            $response = $this->postCheckUpdate($credentials);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Update information retrieved',
                    'license_valid' => true,
                    'current_version' => $this->getUpdateSettings()['current_version'],
                    'requested_version' => $version,
                    'update_info' => $data,
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to get update information: ' . (isset($response->json()['message']) ? $response->json('message') : 'Server error'),
                    'license_valid' => true,
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Get update info exception: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to get update information: ' . $e->getMessage(),
                'license_valid' => true,
            ], 500);
        }
    }

    /**
     * Install/Apply update
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function installUpdate(Request $request)
    {
        // Verify license first
        $licenseCheck = $this->verifyLicense();
        
        if (!$licenseCheck['valid']) {
            return response()->json([
                'status' => 'error',
                'message' => 'License validation failed: ' . $licenseCheck['message']
            ], 403);
        }

        $version = $request->input('version');
        
        if (empty($version)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Version parameter is required'
            ], 400);
        }

        try {
            // Step 1: Get downloaded file info from cache
            $downloadInfo = Cache::get('update_download_' . $version);
            
            if (empty($downloadInfo) || !File::exists($downloadInfo['file_path'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Update file not found. Please download the update first.'
                ], 404);
            }

            $zipPath = $downloadInfo['file_path'];
            $extractPath = storage_path('app/' . self::UPDATE_STORAGE_PATH . '/extracted-' . $version);

            // Step 2: Extract ZIP file
            if (!class_exists('ZipArchive')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ZipArchive extension is not available'
                ], 500);
            }

            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== TRUE) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to open update archive'
                ], 500);
            }

            // Create extraction directory
            if (File::exists($extractPath)) {
                File::deleteDirectory($extractPath);
            }
            File::makeDirectory($extractPath, 0755, true);

            // Extract files
            $zip->extractTo($extractPath);
            $zip->close();

            Log::info('Update extracted', [
                'version' => $version,
                'extract_path' => $extractPath
            ]);

            // Step 3: Run pre-installation checks
            $this->runPreInstallationChecks($extractPath);

            // Step 4: Backup site code (zip) and database before applying update
            $backupPrefix = 'pre-update-' . $version . '-' . date('Y-m-d-His');
            $codeBackupPath = $this->createSiteCodeBackup($backupPrefix);
            $dbBackupPath = $this->createDatabaseBackup($backupPrefix);
            Log::info('Backups created before update', [
                'code_backup' => $codeBackupPath,
                'db_backup' => $dbBackupPath,
            ]);

            // Step 5: Copy update files to application directory
            $this->copyUpdateFiles($extractPath, base_path());

            // Step 6: Run database migrations - any new migration in the update ZIP will run
            Artisan::call('migrate', ['--force' => true]);
            Log::info('Database migrations executed for update version: ' . $version);

            // Step 7: Clear all caches
            Artisan::call('optimize:clear');
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            // Step 8: Clean up extracted files
            File::deleteDirectory($extractPath);
            
            // Optionally keep the ZIP file for rollback, or delete it
            // File::delete($zipPath);

            // Step 9: Update app_version in database
            $setting = GeneralSetting::where('status', 1)->first();
            if ($setting) {
                $setting->app_version = $version;
                $setting->save();
                Cache::forget('general_setting');
            }

            Log::info('Update installed successfully', [
                'version' => $version
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Update installed successfully. Please refresh your browser.',
                'version' => $version,
            ]);

        } catch (\Exception $e) {
            Log::error('Update installation exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to install update: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create site code backup (zip) before update
     *
     * @param string $prefix Backup filename prefix
     * @return string|null Path to backup zip or null on failure
     */
    private function createSiteCodeBackup(string $prefix): ?string
    {
        $backupDir = storage_path('app/' . self::UPDATE_STORAGE_PATH . '/' . self::BACKUP_SUBDIR);
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $zipPath = $backupDir . '/' . $prefix . '-code.zip';

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Log::error('Failed to create code backup zip');
            return null;
        }

        $dirsToBackup = ['app', 'routes', 'resources', 'config', 'database'];
        $basePath = base_path();

        foreach ($dirsToBackup as $dir) {
            $fullPath = $basePath . '/' . $dir;
            if (File::exists($fullPath)) {
                $this->addDirectoryToZip($zip, $fullPath, $dir);
            }
        }

        // Add public assets if exists
        $publicDirs = ['css', 'js', 'images', 'fonts'];
        foreach ($publicDirs as $pd) {
            $fullPath = base_path('public/' . $pd);
            if (File::exists($fullPath)) {
                $this->addDirectoryToZip($zip, $fullPath, 'public/' . $pd);
            }
        }

        $zip->close();

        Log::info('Site code backup created', ['path' => $zipPath]);
        return $zipPath;
    }

    /**
     * Add directory recursively to zip
     */
    private function addDirectoryToZip(ZipArchive $zip, string $path, string $zipPrefix): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filePath = $file->getRealPath();
                $relativePath = $zipPrefix . '/' . substr($filePath, strlen($path) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    /**
     * Create database backup (SQL dump) before update
     *
     * @param string $prefix Backup filename prefix
     * @return string|null Path to backup SQL file or null on failure
     */
    private function createDatabaseBackup(string $prefix): ?string
    {
        $backupDir = storage_path('app/' . self::UPDATE_STORAGE_PATH . '/' . self::BACKUP_SUBDIR);
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $sqlPath = $backupDir . '/' . $prefix . '-database.sql';

        try {
            $config = config('database.connections.mysql');
            $host = $config['host'] ?? '127.0.0.1';
            $port = $config['port'] ?? 3306;
            $database = $config['database'] ?? '';
            $username = $config['username'] ?? '';
            $password = $config['password'] ?? '';

            if (empty($database) || empty($username)) {
                Log::warning('Database backup skipped: invalid config');
                return null;
            }

            $mysqldump = $this->findMysqldump();
            if (!$mysqldump) {
                Log::warning('mysqldump not found, trying PHP fallback for database backup');
                return $this->createDatabaseBackupPhp($sqlPath);
            }

            $args = [$mysqldump, '-h', $host, '-P', (string) $port, '-u', $username];
            if (!empty($password)) {
                $args[] = '-p' . $password;
            }
            $args = array_merge($args, ['--single-transaction', '--routines', '--triggers', $database]);

            $result = Process::run($args, null, 120);
            if ($result->successful() && !empty(trim($result->output()))) {
                File::put($sqlPath, $result->output());
            }
            if (File::exists($sqlPath) && File::size($sqlPath) > 0) {
                Log::info('Database backup created', ['path' => $sqlPath]);
                return $sqlPath;
            }

            // Fallback: PHP-based dump
            Log::warning('mysqldump failed, using PHP fallback');
            return $this->createDatabaseBackupPhp($sqlPath);

        } catch (\Throwable $e) {
            Log::error('Database backup error: ' . $e->getMessage());
            return $this->createDatabaseBackupPhp($sqlPath);
        }
    }

    /**
     * Find mysqldump executable path
     */
    private function findMysqldump(): ?string
    {
        $paths = [
            'mysqldump',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
        ];

        foreach ($paths as $path) {
            if ($path === 'mysqldump') {
                $result = Process::run('mysqldump --version');
                if ($result->successful()) {
                    return 'mysqldump';
                }
            } elseif (file_exists($path)) {
                return $path;
            }
        }
        return null;
    }

    /**
     * Create database backup using PHP (fallback when mysqldump unavailable)
     */
    private function createDatabaseBackupPhp(string $sqlPath): ?string
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $dbName = config('database.connections.mysql.database');
            $key = 'Tables_in_' . $dbName;

            $output = "-- Database backup via PHP\n-- " . now()->toDateTimeString() . "\n\n";
            $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                $arr = (array) $table;
                $tableName = $arr[$key] ?? reset($arr);
                $output .= "DROP TABLE IF EXISTS `" . $tableName . "`;\n";
                $create = DB::selectOne("SHOW CREATE TABLE `{$tableName}`");
                $createCol = 'Create Table';
                $output .= ($create->{$createCol} ?? '') . ";\n\n";

                $rows = DB::table($tableName)->get();
                foreach ($rows as $row) {
                    $values = array_map(function ($v) {
                        return $v === null ? 'NULL' : "'" . addslashes((string) $v) . "'";
                    }, (array) $row);
                    $output .= "INSERT INTO `{$tableName}` VALUES (" . implode(',', $values) . ");\n";
                }
                $output .= "\n";
            }

            $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
            File::put($sqlPath, $output);

            Log::info('Database backup created (PHP fallback)', ['path' => $sqlPath]);
            return $sqlPath;
        } catch (\Throwable $e) {
            Log::error('PHP database backup failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Run pre-installation checks
     */
    private function runPreInstallationChecks(string $extractPath): void
    {
        // Check if required directories exist
        $requiredDirs = ['app', 'routes', 'resources'];
        foreach ($requiredDirs as $dir) {
            if (!File::exists($extractPath . '/' . $dir)) {
                throw new \Exception("Required directory '{$dir}' not found in update package");
            }
        }
    }

    /**
     * Copy update files to application directory
     */
    private function copyUpdateFiles(string $source, string $destination): void
    {
        $filesToCopy = [
            'app' => 'app',
            'routes' => 'routes',
            'resources' => 'resources',
            'config' => 'config',
            'database/migrations' => 'database/migrations',
        ];

        foreach ($filesToCopy as $sourceDir => $destDir) {
            $sourcePath = $source . '/' . $sourceDir;
            $destPath = $destination . '/' . $destDir;

            if (File::exists($sourcePath)) {
                // Copy directory recursively
                File::copyDirectory($sourcePath, $destPath);
                Log::info("Copied {$sourceDir} to {$destDir}");
            }
        }

        // Copy individual files if specified in update package
        $filesList = $source . '/files.txt';
        if (File::exists($filesList)) {
            $files = explode("\n", File::get($filesList));
            foreach ($files as $file) {
                $file = trim($file);
                if (!empty($file)) {
                    $sourceFile = $source . '/' . $file;
                    $destFile = $destination . '/' . $file;
                    
                    if (File::exists($sourceFile)) {
                        File::ensureDirectoryExists(dirname($destFile));
                        File::copy($sourceFile, $destFile);
                    }
                }
            }
        }
    }
}
