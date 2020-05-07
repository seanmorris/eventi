<?php
namespace SeanMorris\Eventi;
class KayVeeProducer extends Producer
{
	protected const TOPIC_NAME = 'test-kayvee';

	public static function message($topic)
	{
		echo 'Message payload: ' . microtime(true) . "\n";

		$topic->produce(
			RD_KAFKA_PARTITION_UA
			, 0
			, 'Message payload: ' . microtime(true)
			, time() % 10
		);
	}
}
