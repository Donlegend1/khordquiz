<?php

if (PHP_SAPI === 'cli' && ! app()->runningUnitTests()) {
    ini_set('memory_limit', '512M');
}

$recognitionPart = 2;

require __DIR__.'/build_progression_recognition.php';
