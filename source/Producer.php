<?php
namespace SeanMorris\Eventi;

class Producer
{
	public static function emit($i, $j)
	{
		while(TRUE)
		{
			static::produce($i);

			usleep(1 * 1000 * $j);
		}
	}

	public static function produce($i)
	{
		$conf = new \RdKafka\Conf();

		// $conf->set('log_level', (string) LOG_DEBUG);
		// $conf->set('debug', 'all');

		$conf->set('metadata.broker.list', 'kafka:9092');

		$producer = new \RdKafka\Producer($conf);

		$topic = $producer->newTopic("test");

		$x = 0;

		while(TRUE)
		{
			$topic->produce(
				RD_KAFKA_PARTITION_UA
				, 0
				, sprintf('%0.4f', microtime(true))
			);

			$x++;

			if($i <= 0)
			{
				break;
			}

			if($i > 0 && $x >= $i)
			{
				$producer->flush(500);
				$x = 0;
				break;
			}
		}

	}
}
