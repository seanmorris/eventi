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
			, 'body'    => 'User generated message.'
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

		if($lastEvent = intval($router->request()->headers('Last-Event-ID')))
		{
			$newOffset = $lastEvent + 1;

			\SeanMorris\Ids\Log::error('Setting offset', $newOffset);

			KayVeeConsumer::reset('test', $newOffset);
		}

		return new EventSource(function() {

			while(TRUE)
			{
				\SeanMorris\Ids\Log::debug('tick...');

				if(connection_aborted())
				{
					break;
				}

				while($event = KayVeeConsumer::wait('test', 'web-test-group'))
				{
					if(!isset($event->payload))
					{
						continue;
					}

					if($event->payload === 'Broker: No more messages')
					{
						continue;
					}

					ob_start();

					yield new Event($event->payload, (int) $event->offset);

					while(ob_get_level())
					{
						ob_end_flush();
						flush();
					}
				}
			}
		});
	}

	public function session($router)
	{
		$session = \SeanMorris\Ids\Session::local();

		\SeanMorris\Ids\Log::error($session);

		return 'wow';
	}

	public function _notFound($router)
	{
		return 'Not found.';
	}
}
