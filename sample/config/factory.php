<?php

return [
    'executor' => PHP_OS === 'WINNT' ? 'C:\OpenServer\modules\php\PHP-7.0-x64\php.exe' : null,
    'starter' => '-r "require \'bootstrap.php\';"',
    'multiThreading' => [
        'disabled' => false,
    ]
];
