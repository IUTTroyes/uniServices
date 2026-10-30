<?php

require __DIR__ . '/../back/vendor/autoload.php';

spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'MessageBundle\\Tests\\')) {
        $file = __DIR__ . '/../packages/message-bundle/tests/' . str_replace('\\', '/', substr($class, 20)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

