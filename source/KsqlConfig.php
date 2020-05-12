<?php
namespace SeanMorris\Eventi;
class KsqlConfig
{
	public static function apply()
	{
		$config = (object) yaml_parse_file('/app/data/global/ksql.yml');

		if(isset($config->streams))
		{
			foreach($config->streams as $name => $streamCreate)
			{
				if($check = static::query(sprintf('DESCRIBE `%s`;', $name)))
				{
					$existing = $check[0]->sourceDescription->statement;
					var_dump($streamCreate == $existing);
				}
			}
		}

		if(isset($config->tables))
		{
			foreach($config->tables as $name => $tableCreate)
			{
				if($check = static::query(sprintf('DESCRIBE `%s`;', $name)))
				{
					$existing = $check[0]->sourceDescription->statement;
					var_dump($tableCreate == $existing);
				}

			}
		}
	}

	public static function query($string = '')
	{
		$context = stream_context_create($ctx = ['http' => [
			'method'    => 'POST'
			, 'content' => json_encode(['ksql' => $string])
			, 'header'  => [
				'Content-type: application/x-www-form-urlencoded'
			]
		]]);

		$handle = fopen('http://ksql-server:8088/ksql', 'rb', FALSE, $context);
		$result = stream_get_contents($handle);

		fclose($handle);

		return json_decode($result);
	}
}
