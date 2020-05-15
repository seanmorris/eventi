<?php
namespace SeanMorris\Eventi;
class Ksql
{
	public static function test()
	{
		$ksql = new static('http://ksql-server:8088/');

		return [

			NULL

			// , 'streams'  => $ksql->streams()
			// , 'stream' => $ksql->stream('EVENTS')->toStruct()

			// , 'tables'  => $ksql->tables(1)
			// , 'abc'   => $ksql->table('abc')->drop()
			// , 'xyz'   => $ksql->table('xyz')->drop()

			// // , 'queries' => $ksql->queries(1)
			// // , 'query'   => $ksql->query($qid)->toStruct()

			// , 'createStream' => $ksql->createStream(
			// 	'abc'
			// 	, ['id' => 'STRING']
			// 	, [
			// 		'key' => '`id`'
			// 		, 'KAFKA_TOPIC'  => 'test'
			// 		, 'VALUE_FORMAT' => 'JSON'
			// 	]
			// )->toStruct()

			// , 'createTable' => $ksql->createTable(
			// 	'xyz'
			// 	, ['id' => 'STRING']
			// 	, [
			// 		'key' => '`id`'
			// 		, 'KAFKA_TOPIC'  => 'test'
			// 		, 'VALUE_FORMAT' => 'JSON'
			// 	]
			// )->toStruct()

			, 'statusS' => $ksql
				->statusSource('STREAM', 'abc', 'create')
				->toStruct()
			, 'statusT' => $ksql
				->statusSource('TABLE', 'xyz', 'create')
				->toStruct()
		];
	}

	protected $endpoint;

	public function __construct($endpoint)
	{
		$this->endpoint = $endpoint;
	}

	public function properties()
	{
		[$response] = static::runQuery('SHOW PROPERTIES');

		if(!isset($response->properties))
		{
			return new \SeanMorris\Eventi\Ksql\Error(
				$this, $response->properties
			);
		}

		return $response->properties;
	}

	public function tables($details = FALSE)
	{
		[$response] = static::runQuery('SHOW TABLES');

		$result = [];

		if(!isset($response->tables))
		{
			return new \SeanMorris\Eventi\Ksql\Error(
				$this, $response
			);
		}

		foreach($response->tables as $table)
		{
			$result[ $table->name ] = $table;
		}

		if(!$details)
		{
			return array_keys($result);
		}

		return $result;
	}

	public function streams($details = FALSE)
	{
		[$response] = static::runQuery('SHOW STREAMS');

		$result = [];

		if(!isset($response->streams))
		{
			return new \SeanMorris\Eventi\Ksql\Error(
				$this, $response
			);
		}

		foreach($response->streams as $stream)
		{
			$result[ $stream->name ] = $stream;
		}

		if(!$details)
		{
			return array_keys($result);
		}

		return $result;
	}

	public function queries($details = FALSE)
	{
		[$response] = static::runQuery('SHOW QUERIES');

		$result = [];

		if(!isset($response->queries))
		{
			return new \SeanMorris\Eventi\Ksql\Error(
				$this, $response
			);
		}

		foreach($response->queries as $query)
		{
			$result[ $query->id ] = $query;
		}

		if(!$details)
		{
			return array_keys($result);
		}

		return $result;
	}

	public function table($tableName)
	{
		[$response] = static::describe($tableName);

		if(!isset($response->sourceDescription))
		{
			return new \SeanMorris\Eventi\Ksql\Error(
				$this, $response
			);
		}

		return new \SeanMorris\Eventi\Ksql\Table(
			$this, $response->sourceDescription
		);

		return $response;
	}

	public function stream($tableName)
	{
		[$response] = static::describe($tableName);

		if(!isset($response->sourceDescription))
		{
			return new \SeanMorris\Eventi\Ksql\Error(
				$this, $response
			);
		}

		return new \SeanMorris\Eventi\Ksql\Stream(
			$this, $response->sourceDescription
		);

		return $response;
	}

	public function query($queryId)
	{
		[$response] = static::explain($queryId);

		if(!isset($response->queryDescription))
		{
			return new \SeanMorris\Eventi\Ksql\Error(
				$this, $response
			);
		}

		return new \SeanMorris\Eventi\Ksql\Query(
			$this, $response->queryDescription
		);
	}

	public static function escapeId($identifier)
	{
		return '`' . str_replace('`', '``', $identifier) . '`';
	}

	public static function escape($string)
	{
		return "'" . str_replace("'", "''", $string) . "'";
	}

	public function createStream($resourceName, $columns, $with = [])
	{
		return $this->createSource(
			'STREAM'
			, $resourceName
			, $columns
			, $with
		);
	}

	public function createTable($resourceName, $columns, $with = [])
	{
		return $this->createSource(
			'TABLE'
			, $resourceName
			, $columns
			, $with
		);
	}

	public function createSource($sourceType, $resourceName, $columns, $with = [])
	{
		$_columns = [];

		foreach($columns as $column => $type)
		{
			$_columns[] = static::escapeId($column)
				. ' '
				.  static::escapeId($type);
		}

		$_with = [];

		foreach($with as $property => $value)
		{
			$_with[] = strtoupper(static::escapeId($property))
				. ' = '
				. static::escape($value);
		}

		[$response] = static::runQuery(sprintf(
			'CREATE %s %s ( %s ) WITH ( %s )'
			, $sourceType
			, static::escapeId($resourceName)
			, implode(', ', $_columns)
			, implode(', ', $_with)
		));

		if($response->error_code ?? 0)
		{
			return new \SeanMorris\Eventi\Ksql\Error(
				$this, $response
			);
		}

		if($response->error_code ?? 0)
		{
			return new \SeanMorris\Eventi\Ksql\Error(
				$this, $response
			);
		}

		return new \SeanMorris\Eventi\Ksql\Status(
			$this, $response->status
		);
	}

	public function statusSource($sourceType, $resourceName, $action)
	{
		$response = static::get(sprintf(
			'status/%s/%s/%s'
			, urlencode($sourceType)
			, urlencode(static::escapeId($resourceName))
			, urlencode($action)
		));

		$response = json_decode(stream_get_contents($response->stream));

		return new \SeanMorris\Eventi\Ksql\Status(
			$this, $response
		);
	}



	public static function streamUserMessages($limit = NULL)
	{
		return static::streamQuery(<<<EOQ
			SELECT ROWTIME, *
			FROM  `event_table`
			WHERE `body` = 'User generated message.'
			EMIT CHANGES
			EOQ
			, 'latest'
		);
	}

	public static function streamMessages($limit = NULL)
	{
		return static::streamQuery(<<<EOQ
			SELECT ROWTIME, *
			FROM  `event_table`
			EMIT CHANGES
			EOQ
			, 'latest'
		);
	}

	public static function streamServerMessages()
	{
		return static::streamQuery(<<<EOQ
			SELECT ROWTIME, *
			FROM  `event_table`
			WHERE `body` = 'Server generated message.'
			EMIT CHANGES
			EOQ
			, 'latest'
		);
	}

	public static function queryUserMessages()
	{
		return static::streamQuery(<<<EOQ
			SELECT ROWTIME, *
			FROM  `event_table`
			WHERE `body` = 'User generated message.'
			EMIT CHANGES
			EOQ
			, 'latest'
		);
	}

	public static function queryServerMessages()
	{
		return static::streamQuery(<<<EOQ
			SELECT ROWTIME, *
			FROM  `event_table`
			WHERE `body` = 'Server generated message.'
			EMIT CHANGES
			EOQ
			, 'latest'
		);
	}

	public static function run(...$input)
	{
		if($input[0] === 'SELECT')
		{
			return static::stream(implode(' ', $input));
		}

		return static::runQuery(implode(' ', $input));
	}

	public static function describe($resourceName)
	{
		$resourceName = str_replace('`', '``', $resourceName);

		return static::runQuery(sprintf('DESCRIBE EXTENDED `%s`', $resourceName));
	}

	public static function explain($queryId)
	{
		$resourceName = str_replace('`', '``', $queryId);

		return static::runQuery(sprintf('EXPLAIN `%s`', $queryId));
	}

	public static function info()
	{
		$response = static::get('info');

		\SeanMorris\Ids\Log::debug('Waiting...');

		return json_decode(stream_get_contents($response->stream));
	}

	public static function get($path, $content = NULL)
	{
		return static::openRequest('GET', $path, $content);
	}

	public static function post($path, $content = NULL)
	{
		return static::openRequest('POST', $path, $content);
	}

	public static function openRequest($method, $path, $content = NULL)
	{
		$context = stream_context_create(['http' => [
			'ignore_errors' => true
			, 'content'     => $content
			, 'method'      => $method
			, 'header'      => [
				'Content-Type: application/json; charset=utf-8'
				, 'Accept: application/vnd.ksql.v1+json'
			]
		]]);

		$handle = fopen('http://ksql-server:8088/' . $path, 'r', FALSE, $context);

		return array_reduce($http_response_header, function($carry, $header){

			if(stripos($header, 'HTTP/') === 0)
			{
				$header = strtoupper($header);

				[$httpVer, $code, $status] = sscanf(
					$header, 'HTTP/%s %s %[ -~]'
				);

				$spacePos = strpos($header, ' ');

				$carry->code   = (int) $code;
				$carry->http   = $httpVer;
				$carry->status = substr($header, 1 + $spacePos);
			}

			if(($split = stripos($header, ':')) !== FALSE)
			{
				$key   = substr($header, 0, $split);
				$value = substr($header, 1 + $split);

				$carry->header->$key = ltrim($value);
			}

			return $carry;

		}, (object) [
			'http'     => 0
			, 'code'   => 0
			, 'status' => 'ERROR'
			, 'header' => (object) []
			, 'stream' => $handle
		]);
	}

	public static function runQuery(...$strings)
	{
		foreach($strings as $i => &$s)
		{
			if($i > 0 && is_array($s))
			{
				$s = sprintf(...$s);
			}
		}

		$string = implode(';', $strings) . ';';

		\SeanMorris\Ids\Log::query('Issuing KSQL query...', $string);

		$response = static::post('ksql', json_encode([
			'ksql' => $string
		]));

		\SeanMorris\Ids\Log::debug('Waiting...');

		$rawResponse = stream_get_contents($response->stream);

		if(!$response = json_decode($rawResponse))
		{
			throw new \UnexpectedValueException(
				'Unexpected formatting on query response.'
			);
		}

		if(is_object($response))
		{
			$response = [$response];
		}

		return $response;
	}

	public const HTTP_OK = 200;

	public static function streamQuery($string, $reset = 'latest')
	{
		\SeanMorris\Ids\Log::query('Streaming KSQL query...', $string);

		$response = static::post('query', json_encode([
			'ksql' => $string . ';'
			, 'streamsProperties' => [
				'ksql.streams.auto.offset.reset' => $reset
			]
		]));

		if($response->code !== static::HTTP_OK)
		{
			\SeanMorris\Ids\Log::debug($response);
			\SeanMorris\Ids\Log::warn(json_decode(
				stream_get_contents($response->stream)
			));

			return FALSE;
		}

		\SeanMorris\Ids\Log::debug('Waiting...');

		stream_set_chunk_size($response->stream, 1);
		stream_set_read_buffer($response->stream, 0);

		while($message = fgets($response->stream))
		{
			if(!$message = rtrim($message))
			{
				continue;
			}

			$message = substr($message, 0, -1);

			[$message] = sscanf($message, '[%[^\0]');
			break;
		}

		\SeanMorris\Ids\Log::debug('Got...', $message);

		if(!$record = json_decode($message))
		{
			throw new \UnexpectedValueException(
				'Unexpected formatting on first line of stream.'
			);
		}

		if(!($record->header ?? NULL) || !($record->header->schema ?? NULL))
		{
			throw new \UnexpectedValueException(
				'Unexpected data structure on first line of stream.'
			);
		}

		$keyTypes = [];
		$keyDefs  = explode(', ', $record->header->schema);

		foreach($keyDefs as $keyDef)
		{
			[$key, $type] = sscanf($keyDef, '`%[^\`]` %s');

			$keyTypes[ $key ] = $type;
		}

		$keys = array_keys($keyTypes);

		\SeanMorris\Ids\Log::debug('Keys decoded...', $keyTypes);

		while($message = fgets($response->stream))
		{
			if(!$message = rtrim($message))
			{
				continue;
			}

			$message = substr($message, 0, -1);

			\SeanMorris\Ids\Log::debug('got...', $message);

			if(!$record = json_decode($message))
			{
				throw new \UnexpectedValueException(
					'Unexpected formatting in stream body.'
				);
			}

			if($record->finalMessage ??0)
			{
				break;
			}

			if(!($record->row ??0) || !($record->row->columns ??0))
			{
				throw new \UnexpectedValueException(
					'Unexpected data structure in stream body.'
				);
			}

			$entry = (object) array_combine(
				$keys, (array) $record->row->columns
			);

			\SeanMorris\Ids\Log::debug('Entry decoded...', $entry);

			yield $entry;
		}

		fclose($response->stream);
	}
}
