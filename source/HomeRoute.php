<?php
namespace SeanMorris\Eventi;
class HomeRoute implements \SeanMorris\Ids\Routable
{
	public function send($router)
	{
		if($router->request()->method() !== 'POST')
		{
			return;
		}

		$conf = new \RdKafka\Conf();

		$conf->set('metadata.broker.list', 'kafka:9092');

		$producer = new \RdKafka\Producer($conf);
		$topic    = $producer->newTopic("test");

		$message  = json_encode((object)[
			'id'        => uuid_create()
			, 'created' => sprintf('%0.8f', microtime(true))
			, 'body'    => 'User generaged message.'
		]);

		$topic->produce(RD_KAFKA_PARTITION_UA, 0, $message);

		$producer->flush(500);

		return $message;
	}

	public function events($router)
	{
		header('Cache-Control: no-cache');
		header('Content-Type: text/event-stream');

		\SeanMorris\Ids\Log::info('Sending stream...');

		while(ob_get_level())
		{
			ob_end_flush();
			flush();
		}

		return new EventSource(function() {

			while(TRUE)
			{
				\SeanMorris\Ids\Log::debug('tick...', connection_aborted());

				if(connection_aborted())
				{
					break;
				}

				while($event = KayVeeConsumer::wait('test', 'web-test-group'))
				{
					if($event->payload === 'Broker: No more messages')
					{
						continue;
					}

					ob_start();

					yield new Event($event->payload, $event->offset);

					while(ob_get_level())
					{
						ob_end_flush();
						flush();
					}
				}
			}
		});
	}

	public function _notFound($router)
	{
		return 'Not found.';
	}
}
