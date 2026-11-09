<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Support\NoteSample;
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

    // NoteSample::url() adds a `?v={mtime}` cache-buster for note/{slug}/...
    // paths specifically (a sample bank gets rebuilt in place at the same
    // filename whenever a range gets corrected, so without this a browser
    // that already loaded the old audio keeps it from cache indefinitely —
    // and falls back to a plain Storage URL for every other kind of media
    // this is also used for (video, reference audio, uploaded question
    // audio), so this covers both without a separate helper.
    private function mediaUrl(?string $path): ?string
    {
        return $path ? NoteSample::url($path) : null;
    }
}
