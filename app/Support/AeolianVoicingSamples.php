<?php

namespace App\Support;

class AeolianVoicingSamples extends ModalVoicingSamples
{
    public const TITLE = 'Aeolian Mode';

    public const LEGACY_TITLE = self::TITLE;

    public const PREFIX = 'modal-voicings/aeolian-mode-c-v1';

    /** Six matched chord voicings, each altering one tone above a fixed C3 bass. */
    public const VOICINGS = [
        'aeolian' => ['Aeolian', [48, 51, 56, 58, 62, 67]],
        'aeolian-flat-two' => ['Aeolian ♭2', [48, 51, 56, 58, 61, 67]],
        'aeolian-natural-six' => ['Aeolian ♮6', [48, 51, 57, 58, 62, 67]],
        'aeolian-natural-seven' => ['Aeolian ♮7', [48, 51, 56, 59, 62, 67]],
        'aeolian-dominant' => ['Aeolian Dominant', [48, 52, 56, 58, 62, 67]],
        'aeolian-flat-five' => ['Aeolian ♭5', [48, 51, 56, 58, 62, 66]],
    ];
}
