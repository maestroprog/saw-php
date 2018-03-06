<?php

namespace Maestroprog\Saw\Connector;

use Esockets\Client;
use Maestroprog\Saw\Sample\Timings;
use Maestroprog\Saw\Service\AsyncBus;

/**
 * @property Client $client
 */
trait ControllerConnectorTrait
{
    public function work(): \Generator
    {
        global $sleep;
        while (true) {
            $socket = $this->client->getConnectionResource()->getResource();
            $read = [$socket];
            $write = $except = [];
            // TODO decrease select timeout
            $time = microtime(true);
            Timings::start('SELECT');
            if (socket_select($read, $write, $except, 1)) {
                Timings::clock('SELECT');
                $sleep += (microtime(true) - $time);
                Timings::start('ESOCKETS');
                $this->client->read();
                Timings::clock('ESOCKETS');
            } else {
                Timings::clock('SELECT');
                $sleep += (microtime(true) - $time);
            }

            yield 'CONNECTOR' => AsyncBus::SIGNAL_PAUSE;
        }
    }
}
