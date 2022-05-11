<?php

namespace FourLinux\ChatBackend;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Table;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class ChatServer
{

    public const EVENT_START = 'start';
    public const EVENT_OPEN = 'open';
    public const EVENT_MESSAGE = 'message';
    public const EVENT_REQUEST = 'request';
    public const EVENT_CLOSE = 'close';

    private string $host;

    private int $port;

    private Table $connections;

    private Server $server;

    private Rocket $rocket;

    private RocketWebhookHandler $httpRequestHandler;

    public function __construct(
        $host = '0.0.0.0',
        $port = 9502
    ) {
        $this->host = $host;
        $this->port = $port;

        $this->rocket = new Rocket();

        $this->connections = new Table(1024);
        $this->connections->column('token', Table::TYPE_STRING, 32);
        $this->connections->column('room', Table::TYPE_STRING, 32);
        $this->connections->create();

        $this->buildServer();

        $this->httpRequestHandler = new RocketWebhookHandler($this->server, $this->connections);
    }

    private function buildServer(): void
    {
        $this->server = new Server($this->host, $this->port);

        $this->on(self::EVENT_START, 'onStart');
        $this->on(self::EVENT_OPEN, 'onOpen');
        $this->on(self::EVENT_MESSAGE, 'onMessage');
        $this->on(self::EVENT_REQUEST, 'onRequest');
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

        [, $body] = $this->rocket->createVisitor($request->fd);
        $data = json_decode($body, true);
        $token = $data['visitor']['token'];

        [, $body] = $this->rocket->createRoom($token);
        $data = json_decode($body, true);
        $roomId = $data['room']['_id'];

        $this->connections->set($request->fd, [
            'token' => $token,
            'room' => $roomId,
        ]);
    }

    private function onMessage(Server $server, Frame $frame): void
    {
        $data = json_decode($frame->data, true);

        $connectionData = $this->connections->get($frame->fd);

        $this->rocket->sendMessage($connectionData['token'], $connectionData['room'], $data['text']);
    }

    private function onRequest(Request $request, Response $response): void
    {
        $this->httpRequestHandler->handle($request, $response);
    }

    private function onClose(Server $server, int $fd): void
    {
        echo "connection close: {$fd}", PHP_EOL;
    }

}
