<?php

return [
    'name' => 'SimpleCaptcha',

    'defaults' => [
        'enabled' => false,
        'type' => 'math', // math, image
        'difficulty' => 'easy', // easy, medium, hard
        'case_sensitive' => false, // for image captcha
        'length' => 5, // for image captcha
        'expiry' => 300, // seconds (5 minutes)
        'protected_forms' => [
            'login' => true,
            'register' => true,
            'checkout_guest' => true,
            'contact' => true,
            'newsletter' => false,
            'reviews' => false,
        ],
        'sort_order' => 1,
    ],
];