<?php

use Esockets\Base\Configurator;
use Esockets\Protocol\EasyDataGram;
use Esockets\Socket\SocketFactory;

return [
    Configurator::CONNECTION_TYPE => Configurator::CONNECTION_TYPE_SOCKET,
    Configurator::CONNECTION_CONFIG => [
        SocketFactory::SOCKET_DOMAIN => AF_INET,
        SocketFactory::SOCKET_PROTOCOL => SOL_UDP,
    ],
    Configurator::PROTOCOL_CLASS => EasyDataGram::class,
];
