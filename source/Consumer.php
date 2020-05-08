<?php
namespace SeanMorris\Eventi;

class Consumer
{
	public static function emit($topic, $groupId)
	{
		static::listen($topic, $groupId, function($message){
			switch ($message->err)
			{
				case RD_KAFKA_RESP_ERR_NO_ERROR:
					\SeanMorris\Ids\Log::debug($message);
					break;

				case RD_KAFKA_RESP_ERR__PARTITION_EOF:
					\SeanMorris\Ids\Log::debug('No more messages; waiting...');
					break;

				case RD_KAFKA_RESP_ERR__TIMED_OUT:
					\SeanMorris\Ids\Log::debug('Timed out.');
					break;

				default:
					\SeanMorris\Ids\Log::warn($message);
					throw new \Exception($message->errstr(), $message->err);
					break;
			}
		});
	}

	public static function wait($topic, $groupId)
	{
		static $conf, $consumer;

		if(!$conf)
		{
			$conf = new \RdKafka\Conf();

			$conf->set('group.id',             $groupId);
			$conf->set('metadata.broker.list', 'kafka:9092');
			$conf->set('auto.offset.reset',    'smallest');

			$conf->setRebalanceCb([static::class, 'rebalance']);

			// $conf->set('log_level', (string) LOG_DEBUG);
			// $conf->set('debug', 'all');

			$consumer = new \RdKafka\KafkaConsumer($conf);

			$consumer->subscribe([$topic]);
		}

		return $consumer->consume(120*1000);
		// $consumer->commit($message);
	}

	public static function listen($topic, $groupId, $callback)
	{
		while (true)
		{
			$message = static::wait($topic, $groupId);

			$callback($message);
		}
	}

	public static function rebalance(
		\RdKafka\KafkaConsumer $kafka
		, $error = NULL
		, array $partitions = NULL
	){
		switch ($error)
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
				throw new \Exception($error);
	    }
	}
}
