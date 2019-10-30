<?php

spl_autoload_register(function ($cls) {
    $map = [
        'Swift_Transport_StreamBuffer' => __DIR__ . '/StreamBuffer.php',
    ];

    if (isset($map[$cls])) {
        include $map[$cls];
        return true;
    }
}, true, true);
