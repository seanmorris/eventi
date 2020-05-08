<?php
namespace SeanMorris\Eventi;
class KayVeeProducer extends Producer
{
	protected const TOPIC_NAME = 'test-kayvee';

	public static function message($topic)
	{
		\SeanMorris\Ids\Log::debug('Message payload: ' . microtime(true) . "\n");

		$topic->produce(
			RD_KAFKA_PARTITION_UA
			, 0
			, sprintf('%0.4f', microtime(true))
			, time() % 10
		);
	}
}
