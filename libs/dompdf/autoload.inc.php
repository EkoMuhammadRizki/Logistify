<?php
// Minimal PSR-4 style autoloader for Dompdf when Composer is not used.
spl_autoload_register(function ($class) {
    // Only handle Dompdf namespace
    if (strpos($class, 'Dompdf\\') !== 0) {
        return;
    }

    $baseDir = __DIR__;
    $relative = substr($class, strlen('Dompdf\\'));

    // Try src/ first (PSR-4)
    $srcFile = $baseDir . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($srcFile)) {
        require_once $srcFile;
        return;
    }

    // Fallback for classmap in lib/ (e.g., Dompdf\Cpdf)
    $libFile = $baseDir . '/lib/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($libFile)) {
        require_once $libFile;
        return;
    }
});