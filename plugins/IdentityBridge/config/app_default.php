<?php
declare(strict_types=1);

use IdentityBridge\Enum\AuthenticationMode;

return [
    'IdentityBridge' => [
        'provider' => null,
        'providerConfig' => [],
        'resolver' => null,
        'mode' => AuthenticationMode::ProtectedByDefault->value,
        'overrides' => [
            'Api/Auth/*' => false,
            'Api/Health/index' => false,
        ],
    ],
];
