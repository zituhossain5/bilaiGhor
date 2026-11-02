<?php

namespace App\Services;

use RuntimeException;

/** Streaming authenticated encryption using libsodium secretstream XChaCha20-Poly1305. */
class BackupEncryptionService
{
    private const MAGIC = "BILAI-GHOR-DB-BACKUP-1\n";

    public function assertConfigured(): void
    {
        $key = $this->key();
        sodium_memzero($key);
    }

    /** @return array{path: string, filename: string, size: int} */
    public function encrypt(array $backup): array
    {
        $key = $this->key();

        $source = @fopen($backup['path'], 'rb');
        if ($source === false) {
            sodium_memzero($key);
            throw new RuntimeException('Backup encryption source is unavailable');
        }

        $path = $backup['path'].'.enc';
        $target = @fopen($path, 'wb');
        if ($target === false) {
            fclose($source);
            sodium_memzero($key);
            throw new RuntimeException('Backup encryption output is unavailable');
        }

        $complete = false;
        try {
            [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push($key);
            sodium_memzero($key);
            $this->write($target, self::MAGIC.$header);

            do {
                $chunk = fread($source, 1024 * 1024);
                if ($chunk === false) throw new RuntimeException('Backup encryption read failed');
                $final = feof($source);
                $ciphertext = sodium_crypto_secretstream_xchacha20poly1305_push(
                    $state, $chunk, '',
                    $final ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE
                );
                $this->write($target, pack('N', strlen($ciphertext)).$ciphertext);
            } while (! $final);

            $complete = true;
        } finally {
            fclose($source);
            fclose($target);
            if (! $complete) @unlink($path);
        }

        @chmod($path, 0600);
        return ['path' => $path, 'filename' => $backup['filename'].'.enc', 'size' => filesize($path)];
    }

    private function write($stream, string $bytes): void
    {
        $length = strlen($bytes);
        for ($offset = 0; $offset < $length; $offset += $written) {
            $written = fwrite($stream, substr($bytes, $offset));
            if ($written === false || $written === 0) throw new RuntimeException('Backup encryption write failed');
        }
    }

    private function key(): string
    {
        if (! extension_loaded('sodium')) {
            throw new RuntimeException('Backup encryption support is unavailable');
        }
        $encoded = (string) config('backups.encryption.key');
        $key = str_starts_with($encoded, 'base64:') ? base64_decode(substr($encoded, 7), true) : false;
        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES) {
            throw new RuntimeException('Backup encryption key is not configured correctly');
        }
        return $key;
    }
}
