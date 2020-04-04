<?php
namespace SeanMorris\Eventi;

class Producer
{
	public static function produce()
	{
		$conf = new \RdKafka\Conf();

		// $conf->set('log_level', (string) LOG_DEBUG);
		// $conf->set('debug', 'all');

		$conf->set('metadata.broker.list', 'kafka:9092');

		$producer = new \RdKafka\Producer($conf);

		$topic = $producer->newTopic("test");

		$x = 0;

		echo "Sending...\n";

		while(TRUE)
		{
			echo 'Message payload: ' . microtime(true) . "\n";

			$topic->produce(
				RD_KAFKA_PARTITION_UA
				, 0
				, 'Message payload: ' . microtime(true)
			);

			if($x++ >= 5)
			{
				$producer->flush(500);
				$x = 0;
				break;
			}
		}

	}
}
