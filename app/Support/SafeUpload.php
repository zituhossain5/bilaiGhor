<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Saves an uploaded file under a name we choose. The extension comes from the file's
 * contents (never from the name the uploader sent) and must be on an allow-list, so a
 * script disguised as an image ("shell.php", "x.php.jpg") can never land as runnable code.
 */
class SafeUpload
{
    public const IMAGES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    public const VIDEOS = ['mp4', 'webm', 'ogv', 'mov', 'm4v'];

    /**
     * @param  string  $directory  absolute folder, or one relative to the app root (as the callers use)
     * @return string  the new file name (no folder)
     *
     * @throws ValidationException when the content is not one of the allowed types
     */
    public static function move(UploadedFile $file, string $directory, string $prefix, array $allowed, string $field = 'file'): string
    {
        $extension = strtolower((string) $file->guessExtension());
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }
        if ($extension === 'qt') {
            $extension = 'mov';
        }

        if (!in_array($extension, $allowed, true)) {
            throw ValidationException::withMessages([
                $field => 'এই ধরনের ফাইল আপলোড করা যাবে না। অনুমোদিত: ' . implode(', ', $allowed),
            ]);
        }

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $name = $prefix . time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $file->move($directory, $name);

        return $name;
    }
}
