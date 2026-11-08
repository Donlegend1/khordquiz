<?php

if (PHP_SAPI === 'cli' && ! app()->runningUnitTests()) {
    ini_set('memory_limit', '1024M');
}

$recognitionPart = 4;

require __DIR__.'/build_progression_recognition.php';
