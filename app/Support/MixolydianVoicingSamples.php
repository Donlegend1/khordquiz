<?php

namespace App\Support;

class MixolydianVoicingSamples extends ModalVoicingSamples
{
    public const TITLE = 'Mixolydian Mode';

    public const LEGACY_TITLE = 'Modal Voicings · Level 2';

    public const PREFIX = 'modal-voicings/mixolydian-mode-c-v1';

    /** C3 bass with the agreed five-note right-hand chords in matched positions. */
    public const VOICINGS = [
        'mixolydian' => ['Mixolydian', [48, 52, 57, 58, 62, 65]],
        'mixolydian-flat-two' => ['Mixolydian ♭2', [48, 52, 57, 58, 61, 65]],
        'mixolydian-flat-six' => ['Mixolydian ♭6', [48, 52, 56, 58, 62, 65]],
        'mixolydian-flat-two-flat-six' => ['Mixolydian ♭2 ♭6', [48, 52, 56, 58, 61, 65]],
        'mixolydian-sharp-four' => ['Mixolydian ♯4', [48, 52, 57, 58, 62, 66]],
    ];
}
