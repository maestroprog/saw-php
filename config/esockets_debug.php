<?php

use Esockets\base\Configurator;
use Esockets\socket\SocketFactory;

require_once 'LoggingProtocol.php';

return [
    Configurator::CONNECTION_TYPE => Configurator::CONNECTION_TYPE_SOCKET,
    Configurator::CONNECTION_CONFIG => [
        SocketFactory::SOCKET_DOMAIN => AF_INET,
        SocketFactory::SOCKET_PROTOCOL => SOL_UDP,
    ],
    Configurator::PROTOCOL_CLASS => \Esockets\protocol\EasyDataGram::class,
//    Configurator::PROTOCOL_CLASS => LoggingProtocol::withRealProtocolClass(\Esockets\protocol\EasyDataGram::class),
];
