<?php

namespace App\Support;

class DorianVoicingSamples extends ModalVoicingSamples
{
    public const TITLE = 'Dorian Mode';

    public const LEGACY_TITLE = self::TITLE;

    public const PREFIX = 'modal-voicings/dorian-mode-c-v1';

    /** Seven-note chords retain the minor third and natural sixth in every variant. */
    public const VOICINGS = [
        'dorian' => ['Dorian', [48, 51, 55, 57, 58, 62, 65]],
        'dorian-flat-two' => ['Dorian ♭2', [48, 51, 55, 57, 58, 61, 65]],
        'dorian-sharp-four' => ['Dorian ♯4', [48, 51, 55, 57, 58, 62, 66]],
        'dorian-flat-five' => ['Dorian ♭5', [48, 51, 54, 57, 58, 62, 65]],
        'dorian-natural-seven' => ['Dorian ♮7', [48, 51, 55, 57, 59, 62, 65]],
    ];
}
