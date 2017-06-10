<?php

return [
    'executor' => PHP_OS === 'WINNT' ? 'С:\OpenServer\modules\php\PHP-7.0\php.exe' : null,
    'starter' => '-r "require \'bootstrap.php\';"',
];
