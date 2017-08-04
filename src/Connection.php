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
	 * @var string
	 */
	protected $uri;

	/**
	 * @var array
	 */
	protected $options;


	/**
	 * @throws ConnectionException
	 */
	public function __construct(string $uri, array $options)
	{
		$this->uri = $uri;
		$this->options = $options;
	}


	public static function factory(string $uri, array $options = []): Connection
	{
		if (!isset($options['connectionTimeout']) || !is_int($options['connectionTimeout'])) {
			$options['connectionTimeout'] = 30;
		}

		if (!isset($options['streamTimeout']) || !is_int($options['streamTimeout'])) {
			$options['streamTimeout'] = 30;
		}

		return new static($uri, $options);
	}


	public function sendMessage(string $message): ?string
	{
		fwrite($this->getSocketStream(), $message, strlen($message));

		$response = null;

		while ($chunk = fread($this->getSocketStream(), 1024)) {
			$response .= $chunk;

			if (substr($chunk, -1) == "\n") {
				break;
			}
		}

		return $response;
	}


	public function sendMessageWithoutAnswer(string $message): void
	{
		fwrite($this->socketStream, $message, strlen($message));
	}


	/**
	 * @throws ConnectionException
	 */
	protected function getSocketStream()
	{
		/**
		 * FIrst time here?
		 */
		if (!is_resource($this->socketStream)) {
			/**
			 * @throws ConnectionException
			 */
			$this->connect();

			return $this->socketStream;
		}

		/**
		 * Does the connection failed?
		 */
		$meta = stream_get_meta_data($this->socketStream);

		if (($meta['timed_out'] || $meta['eof'])) {
			/**
			 * @throws ConnectionException
			 */
			$this->connect();
		}

		return $this->socketStream;
	}


	protected function connect(): void
	{
		$socketStream = @stream_socket_client( // Intentionally @
			$this->uri,
			$errorNumber,
			$errorMessage,
			$this->options['connectionTimeout']
		);

		if (!is_resource($socketStream)) {
			throw new ConnectionException("{$errorNumber}: Error connecting to socket: [{$errorMessage}]");
		}

		stream_set_timeout($socketStream, $this->options['streamTimeout']);
		stream_set_blocking($socketStream, true);

		$this->socketStream = $socketStream;
	}

}
