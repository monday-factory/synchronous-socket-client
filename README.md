# monday-factory/synchronous-socket-client

Super simple synchronous socket stream client

```php
$options = [
	'connectionTimeout' => 30, // Default value
	'streamTimeout' => 30, // Default value
];

/**
 * @throws ConnectionException
 */
$connection = App\Stream\Connection::factory('tcp://my.super.server:12345', $options);

$response = $connection->sendMessage('{"method": "ping"}');

echo $response;
```
