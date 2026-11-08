<?php

namespace App\Support;

class PhrygianVoicingSamples extends ModalVoicingSamples
{
    public const TITLE = 'Phrygian Mode';

    public const LEGACY_TITLE = 'Modal Voicings · Level 3';

    public const PREFIX = 'modal-voicings/phrygian-mode-c-v1';

    /** Fixed C3 bass and matched right-hand voicings; Fb4 sounds as E4 in Phrygian (♭4). */
    public const VOICINGS = [
        'phrygian' => ['Phrygian', [48, 51, 56, 58, 61, 65]],
        'phrygian-dominant' => ['Phrygian Dominant', [48, 52, 56, 58, 61, 65]],
        'super-phrygian' => ['Phrygian (♭4)', [48, 51, 56, 58, 61, 64]],
        'phrygian-natural-six' => ['Phrygian ♮6', [48, 51, 57, 58, 61, 65]],
        'phrygian-natural-seven' => ['Phrygian ♮7', [48, 51, 56, 59, 61, 65]],
    ];
}
