<?php
declare(strict_types=1);

return [
    'settings' => DI\add([
        'db.user' => 'USER',
        'db.pass' => 'PASSWORD',

        'twitch.clientID' => 'CLIENTID',
        'twitch.clientSecret' => 'CLIENTSECRET',
    ])
];
