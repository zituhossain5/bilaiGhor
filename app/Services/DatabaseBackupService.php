<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseBackupService
{
    public function dumpAvailable(): bool
    {
        return $this->dumpBinary() !== null;
    }

    public function databaseName(): string
    {
        return (string) DB::connection()->getDatabaseName();
    }

    /** @return array{path: string, filename: string, size: int} */
    public function generate(): array
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'mysql') {
            throw new RuntimeException('Unsupported database driver');
        }

        $binary = $this->dumpBinary();
        if ($binary === null) {
            throw new RuntimeException('Database dump binary unavailable');
        }

        $config = $connection->getConfig();
        $directory = storage_path('app/private/backups');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Private backup directory unavailable');
        }
        $this->removeStaleTemporaryFiles($directory);

        $nonce = Str::random(20);
        $credentials = $directory.'/'.$nonce.'.cnf';
        $plainSql = $directory.'/'.$nonce.'.sql';
        $filename = 'bilaighor-db-'.now()->format('Y-m-d-H-i-s').'.sql.gz';
        $compressed = $directory.'/'.$nonce.'-'.$filename;
        $completed = false;

        try {
            $lines = [
                '[client]',
                'user='.$this->optionValue((string) ($config['username'] ?? '')),
                'password='.$this->optionValue((string) ($config['password'] ?? '')),
            ];
            if (! empty($config['unix_socket'])) {
                $lines[] = 'socket='.$this->optionValue((string) $config['unix_socket']);
            } else {
                $lines[] = 'host='.$this->optionValue((string) ($config['host'] ?? 'localhost'));
                $lines[] = 'port='.$this->optionValue((string) ($config['port'] ?? 3306));
                $lines[] = 'protocol=tcp';
            }

            if (file_put_contents($credentials, implode("\n", $lines)."\n", LOCK_EX) === false) {
                throw new RuntimeException('Could not create temporary database credentials');
            }
            @chmod($credentials, 0600);

            $process = new Process([
                $binary,
                '--defaults-extra-file='.$credentials,
                '--single-transaction', '--quick', '--skip-lock-tables',
                '--routines', '--triggers', '--events', '--no-tablespaces',
                '--default-character-set=utf8mb4',
                '--result-file='.$plainSql,
                '--', $this->databaseName(),
            ]);
            $process->setTimeout(600);
            $process->run();
            if (! $process->isSuccessful() || ! is_file($plainSql) || filesize($plainSql) === 0) {
                throw new RuntimeException('Database dump process failed (exit '.$process->getExitCode().')');
            }

            $source = fopen($plainSql, 'rb');
            $target = gzopen($compressed, 'wb6');
            if ($source === false || $target === false) {
                if (is_resource($source)) fclose($source);
                if (is_resource($target)) gzclose($target);
                throw new RuntimeException('Could not compress database dump');
            }
            try {
                while (! feof($source)) {
                    $chunk = fread($source, 1024 * 1024);
                    if ($chunk === false || ($chunk !== '' && gzwrite($target, $chunk) !== strlen($chunk))) {
                        throw new RuntimeException('Database backup compression failed');
                    }
                }
            } finally {
                fclose($source);
                gzclose($target);
            }

            $size = filesize($compressed);
            if ($size === false || $size === 0) {
                throw new RuntimeException('Compressed database backup is empty');
            }
            $completed = true;

            return ['path' => $compressed, 'filename' => $filename, 'size' => $size];
        } finally {
            @unlink($credentials);
            @unlink($plainSql);
            if (! $completed) @unlink($compressed);
        }
    }

    private function optionValue(string $value): string
    {
        if (str_contains($value, "\0")) {
            throw new RuntimeException('Invalid database connection option');
        }

        return '"'.str_replace(
            ["\\", '"', "\n", "\r", "\t"],
            ['\\\\', '\\"', '\\n', '\\r', '\\t'],
            $value
        ).'"';
    }

    private function removeStaleTemporaryFiles(string $directory): void
    {
        foreach (scandir($directory) ?: [] as $name) {
            if (! preg_match('/^[A-Za-z0-9]{20}(?:\\.cnf|\\.sql|-bilaighor-db-\\d{4}-\\d{2}-\\d{2}-\\d{2}-\\d{2}-\\d{2}\\.sql\\.gz)$/', $name)) {
                continue;
            }
            $path = $directory.'/'.$name;
            if (is_file($path) && filemtime($path) < time() - 7200) {
                @unlink($path);
            }
        }
    }

    private function dumpBinary(): ?string
    {
        $configured = config('backups.mysqldump_path');
        $candidates = $configured ? [(string) $configured] : ['mysqldump', 'mariadb-dump'];

        if (! $configured && PHP_OS_FAMILY === 'Windows') {
            $laragonRoot = dirname(PHP_BINARY, 4);
            foreach (glob($laragonRoot.'/bin/mysql/*/bin/{mysqldump,mariadb-dump}.exe', GLOB_BRACE) ?: [] as $path) {
                $candidates[] = $path;
            }
        }

        foreach ($candidates as $candidate) {
            try {
                $probe = new Process([$candidate, '--version']);
                $probe->setTimeout(5);
                $probe->run();
                if ($probe->isSuccessful()) return $candidate;
            } catch (Throwable) {
                // Try the next configured or discovered binary.
            }
        }

        return null;
    }
}
