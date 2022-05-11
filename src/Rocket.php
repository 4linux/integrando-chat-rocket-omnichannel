<?php

namespace FourLinux\ChatBackend;

use Swoole\Coroutine\Http\Client;

class Rocket
{

	private Client $client;

	public function __construct(string $host = 'rocketchat', int $port = 3000, bool $ssl = false)
	{
		$this->client = new Client($host, $port, $ssl);
	}

	public function createVisitor(int $fd): array
	{
		$this->client->setHeaders([
			'Content-Type' => 'application/json',
		]);

		$data = [
			'visitor' => [
				'name' => "Visitante {$fd}",
				'token' => (string)$fd,
			],
		];
		$this->client->post('/api/v1/livechat/visitor', json_encode($data));
		$this->client->close();

		return [
			$this->client->getStatusCode(),
			$this->client->getBody(),
		];
	}

	public function createRoom(string $visitorToken): array
	{
		$query = http_build_query([
			'token' => $visitorToken,
		]);

		$this->client->get("/api/v1/livechat/room?{$query}");
		$this->client->close();

		return [
			$this->client->getStatusCode(),
			$this->client->getBody(),
		];
	}

	public function closeRoom(string $roomId, string $visitorToken): array
	{
		$this->client->setHeaders([
			'Content-Type' => 'application/json',
		]);

		$data = [
			'rid' => $roomId,
			'token' => $visitorToken,
		];

		$this->client->post('/api/v1/livechat/room.close', json_encode($data));
		$this->client->close();

		return [
			$this->client->getStatusCode(),
			$this->client->getBody(),
		];
	}

	public function sendMessage(string $visitorToken, string $roomId, string $message): array
	{
		$this->client->setHeaders([
			'Content-Type' => 'application/json',
		]);

		$data = [
			'msg' => $message,
			'rid' => $roomId,
			'token' => $visitorToken,
		];

		$this->client->post('/api/v1/livechat/message', json_encode($data));
		$this->client->close();

		return [
			$this->client->getStatusCode(),
			$this->client->getBody(),
		];
	}

}
