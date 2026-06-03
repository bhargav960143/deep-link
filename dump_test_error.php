<?php
require __DIR__.'/vendor/autoload.php';

try {
    require_once __DIR__.'/tests/Feature/FirebaseGapAnalysisTest.php';
    echo "OK";
} catch (\Throwable $e) {
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
