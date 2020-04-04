<?php
namespace SeanMorris\Eventi;

class Consumer
{
	public static function listen()
	{
		$conf = new \RdKafka\Conf();

		$conf->set('group.id',             'test-group');
		$conf->set('metadata.broker.list', 'kafka:9092');
		$conf->set('auto.offset.reset',    'smallest');

		$conf->setRebalanceCb([static::class, 'rebalance']);

		// $conf->set('log_level', (string) LOG_DEBUG);
		// $conf->set('debug', 'all');

		$consumer = new \RdKafka\KafkaConsumer($conf);

		$consumer->subscribe(['test']);

		echo "Waiting for partition assignment... (make take some time when\n";
		echo "quickly re-joining the group after leaving it.)\n";

		while (true)
		{
			$message = $consumer->consume(120*1000);

			// $consumer->commit($message);

			switch ($message->err)
			{
				case RD_KAFKA_RESP_ERR_NO_ERROR:
					// var_dump($message->payload);
					var_dump($message->offset);
					break;

				case RD_KAFKA_RESP_ERR__PARTITION_EOF:
					echo "No more messages; waiting...\n";
            		break;

				case RD_KAFKA_RESP_ERR__TIMED_OUT:
					echo "Timed out\n";
					break;

				default:
					throw new \Exception($message->errstr(), $message->err);
					break;
			}
		}
	}

	public static function rebalance(
		\RdKafka\KafkaConsumer $kafka
		, $err = NULL
		, array $partitions = NULL
	){
		switch ($err)
		{
			case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
				echo "Assign: ";
				var_dump($partitions);
				$kafka->assign($partitions);
				break;

			case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
				echo "Revoke: ";
				var_dump($partitions);
				$kafka->assign(NULL);
				break;

			default:
				throw new \Exception($err);
	    }
	}
}
