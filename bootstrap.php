<?php
require_once(($path = __DIR__ . '/src'). '/Reo/Autoload/Autoloader.php');
$autoloader = new Reo\Autoload\Autoloader(
    array(),
    null,
    array(
        'Reo' => $path,
        'org\bovigo\vfs' => ($vendor = '__DIR__' . '/vendor') . '/vfsStream/src/main/php',
        'org\bovigo\vfs\visitor' => $vendor . '/vfsStream/src/main/php/visitor'
    )
);
