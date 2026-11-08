<?php

namespace App\Support;

class LocrianVoicingSamples extends ModalVoicingSamples
{
    public const TITLE = 'Locrian Mode';

    public const LEGACY_TITLE = 'Modal Voicings · Level 4';

    public const PREFIX = 'modal-voicings/locrian-mode-c-v1';

    /** C3 bass and matched right-hand chords, retaining Gb; Fb4 sounds as E4. */
    public const VOICINGS = [
        'locrian' => ['Locrian', [48, 51, 54, 56, 61, 65]],
        'locrian-natural-two' => ['Locrian ♮2', [48, 51, 54, 56, 62, 65]],
        'locrian-natural-six' => ['Locrian ♮6', [48, 51, 54, 57, 61, 65]],
        'locrian-dominant' => ['Locrian Dominant', [48, 52, 54, 56, 61, 65]],
        'super-locrian' => ['Super Locrian', [48, 51, 54, 56, 61, 64]],
    ];
}
