<?php

use Laravel\Fortify\Features;

return [
    'views' => false,
    'home' => '/dashboard',

    'limiters' => [
        'login' => 'login',
    ],

    'features' => [
        Features::registration(),
        Features::resetPasswords(),
    ],
];
