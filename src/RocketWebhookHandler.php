<?php

namespace FourLinux\ChatBackend;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Table;
use Swoole\WebSocket\Server;

class RocketWebhookHandler
{

	private Server $server;

	private Table $connections;

	public function __construct(Server $server, Table $connections)
	{
		$this->server = $server;
		$this->connections = $connections;
	}

	public function handle(Request $request, Response $response): void
	{
		if ($request->server['path_info'] !== '/webhook') {
			$response->setStatusCode(404);
			$response->end();
			return;
		}

		$data = json_decode($request->getContent(), true);

		switch ($data['type']) {
			case 'LivechatSessionStart':
				$this->onSessionStart($data);
				break;
			case 'Message':
				$this->onMessage($data);
				break;
			case 'LivechatSession':
				$this->onClose($data);
				break;
		}

		$response->end(json_encode([
			'message' => 'OK',
		]));
	}

	private function onSessionStart(array $data): void
	{
		$this->server->push($this->getFd($data), json_encode([
			'type' => 'message',
			'text' => 'Chat iniciado',
		]));
	}

	private function onMessage(array $data): void
	{
		$message = $data['messages'][0]['msg'];

		$this->server->push($this->getFd($data), json_encode([
			'type' => 'message',
			'text' => $message,
		]));
	}

	private function getFd(array $data)
	{
		return $data['visitor']['token'];
	}

	private function onClose(array $data): void
	{
		$this->server->push($this->getFd($data), json_encode([
			'type' => 'message',
			'text' => 'Chat finalizado',
		]));

		$this->connections->delete($this->getFd($data));
	}
}
