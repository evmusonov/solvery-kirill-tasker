<?php

spl_autoload_register(function ($class) {

    $class = str_replace('app\\', '', $class);
    $base_dir = __DIR__ . '/';

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});