<?php

declare(strict_types=1);

namespace MondayFactory\SynchronousSocketClient;

use MondayFactory\SynchronousSocketClient\Exception\ConnectionException;

class Connection
{

	/**
	 * @var resource
	 */
	protected $socketStream;


	/**
	 * @throws \InvalidArgumentException
	 */
	public function __construct($socketStream)
	{
		if (!is_resource($socketStream)) {
			throw new \InvalidArgumentException('Given argument is not a valid resource');
		}

		$this->socketStream = $socketStream;
	}


	/**
	 * @throws ConnectionException
	 */
	public static function factory(string $uri, array $options = []): Connection
	{
		if (!isset($options['connectionTimeout']) || !is_int($options['connectionTimeout'])) {
			$options['connectionTimeout'] = 30;
		}

		if (!isset($options['streamTimeout']) || !is_int($options['streamTimeout'])) {
			$options['streamTimeout'] = 30;
		}

		$socketStream = @stream_socket_client( // Intentionally @
			$uri,
			$errorNumber,
			$errorMessage,
			$options['connectionTimeout']
		);

		if (!$socketStream) {
			throw new ConnectionException("{$errorNumber}: Error connecting to socket: [{$errorMessage}]");
		}

		stream_set_timeout($socketStream, $options['streamTimeout']);
		stream_set_blocking($socketStream, true);

		return new static($socketStream);
	}


	public function sendMessage(string $message): ?string
	{
		fwrite($this->socketStream, $message, strlen($message));

		$response = null;

		var_dump(fread($this->socketStream, 100000)); die;

		while ($chunk = fread($this->socketStream, 1024)) {
			$response .= $chunk;

			if (substr($chunk, -1) == "\n") {
				break;
			}
		}

		return $response;
	}

}
