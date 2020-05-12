<?php
namespace SeanMorris\Eventi;
class Ksql
{
	public static function streamUserMessages()
	{
		return static::stream(<<<EOQ
			SELECT ROWTIME, *
			FROM  `event_table`
			WHERE `body` = 'User generated message.'
			EMIT CHANGES
			EOQ
			, 'latest'
		);
	}

	public static function streamServerMessages()
	{
		return static::stream(<<<EOQ
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
		return static::stream(<<<EOQ
			SELECT ROWTIME, *
			FROM  `event_table`
			WHERE `body` = 'User generated message.'
			EMIT CHANGES
			LIMIT 1;
			EOQ
			, 'latest'
		);
	}

	public static function queryServerMessages()
	{
		return static::query(<<<EOQ
			SELECT ROWTIME, *
			FROM  `event_table`
			WHERE `body` = 'Server generated message.'
			EMIT CHANGES
			LIMIT 1;
			EOQ
			, 'latest'
		);
	}

	public static function openRequest($path, $content)
	{
		$context = stream_context_create(['http' => [
			'ignore_errors' => true
			, 'content'     => $content
			, 'method'      => 'POST'
			, 'header'      => [
				'Content-Type: application/json; charset=utf-8'
				, 'Accept: application/vnd.ksql.v1+json'
			]
		]]);

		$handle = fopen('http://ksql-server:8088/' . $path, 'r', FALSE, $context);

		return $handle;
	}

	public static function query($string, $reset = 'latest')
	{
		\SeanMorris\Ids\Log::query('Issuing KSQL query...', $string);

		$handle = static::openRequest('query',json_encode([
			'ksql' => $string . ';'
			, 'streamsProperties' => [
				'ksql.streams.auto.offset.reset' => $reset
			]
		]));

		\SeanMorris\Ids\Log::debug('Waiting...');

		return stream_get_contents($handle);
	}

	public static function stream($string, $reset = 'latest')
	{
		\SeanMorris\Ids\Log::query('Streaming KSQL query...', $string);

		$handle = static::openRequest('query',json_encode([
			'ksql' => $string . ';'
			, 'streamsProperties' => [
				'ksql.streams.auto.offset.reset' => $reset
			]
		]));

		\SeanMorris\Ids\Log::debug('Waiting...');

		$start = fread($handle, 1);

		if($start !== '[')
		{
			return;
		}

		$header = [];

		ob_implicit_flush();

		stream_set_chunk_size($handle, 1);
		stream_set_read_buffer($handle, 0);

		while($line = fgets($handle))
		{
			if(!trim($line))
			{
				continue;
			}

			\SeanMorris\Ids\Log::debug('got...', $line);

			$message   = substr($line, 0, -2);
			$delimiter = substr($line, -2, 1);

			if(!$record = json_decode($message))
			{
				break;
			}


			if(!$header)
			{
				if(!($record->header ?? NULL) || !($record->header->schema ?? NULL))
				{
					break;
				}

				foreach(explode(', ', $record->header->schema) as $h)
				{
					if(!preg_match('/^`(.+?)`\s(.+?)$/', $h, $match))
					{
						continue;
					}

					$header[ $match[1] ] = $match[2];
				}

				continue;
			}

			if(!($record->row ?? NULL) || !($record->row->columns ?? NULL))
			{
				continue;
			}

			$entry = array_combine(
				array_keys($header)
				, (array) $record->row->columns
			);

			\SeanMorris\Ids\Log::debug('decoded...', $entry);

			// echo json_encode($entry) . PHP_EOL;
		}

		fclose($handle);
	}
}
