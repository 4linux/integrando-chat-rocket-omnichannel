<?php

namespace FourLinux\ChatBackend;

use Iterator;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class ChatServer
{

    public const EVENT_START = 'start';
    public const EVENT_OPEN = 'open';
    public const EVENT_MESSAGE = 'message';
    public const EVENT_CLOSE = 'close';

    private string $host;

    private int $port;

    private Server $server;

    public function __construct(
        $host = '0.0.0.0',
        $port = 9502
    ) {
        $this->host = $host;
        $this->port = $port;

        $this->buildServer();
    }

    private function buildServer(): void
    {
        $this->server = new Server($this->host, $this->port);

        $this->on(self::EVENT_START, 'onStart');
        $this->on(self::EVENT_OPEN, 'onOpen');
        $this->on(self::EVENT_MESSAGE, 'onMessage');
        $this->on(self::EVENT_CLOSE, 'onClose');
    }

    private function on(string $event, string $method): void
    {
        $this->server->on($event, fn() => $this->$method(...func_get_args()));
    }

    public function start()
    {
        return $this->server->start();
    }

    private function onStart(Server $server): void
    {
        echo "Swoole WebSocket Server is started at ws://{$this->host}:{$this->port}", PHP_EOL;
    }

    private function onOpen(Server $server, Request $request): void
    {
        echo "connection open: {$request->fd}", PHP_EOL;
    }

    private function onMessage(Server $server, Frame $frame): void
    {
        /** @var Iterator $connections */
        $connections = $server->connections;

        /** @var int $connection */
        foreach ($connections as $connection) {
            if ($connection === $frame->fd) {
                continue;
            }

            $server->push($connection, $frame->data);
        }
    }

    private function onClose(Server $server, int $fd): void
    {
        echo "connection close: {$fd}", PHP_EOL;
    }

}
