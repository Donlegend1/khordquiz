<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait HandlesQuizMedia
{
    /**
     * @param  array<int, string>  $unused
     */
    private function resolveMedia(?UploadedFile $file, ?string $current, bool $keep, string $directory, array &$unused): ?string
    {
        if ($file instanceof UploadedFile) {
            if ($current) {
                $unused[] = $current;
            }

            return $file->store($directory, 'public');
        }

        if ($keep) {
            return $current;
        }

        if ($current) {
            $unused[] = $current;
        }

        return null;
    }

    /**
     * @param  array<int, string|null>  $paths
     */
    private function deleteMedia(array $paths): void
    {
        $paths = array_values(array_filter($paths));

        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }
    }

    private function mediaUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}
