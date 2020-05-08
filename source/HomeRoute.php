<?php
namespace SeanMorris\Eventi;
class HomeRoute implements \SeanMorris\Ids\Routable
{
	public function rangetest()
	{
		$it = new \MultipleIterator(\MultipleIterator::MIT_NEED_ANY);

		$it->attachIterator(new \ArrayIterator(range(0,100)));
		$it->attachIterator(new \ArrayIterator(range(0,10)));
		$it->attachIterator(new \ArrayIterator(array_fill(50,10,10)));
		$it->attachIterator(new \ArrayIterator(range(0,100)));

		foreach($it as $k => $v)
		{
			var_dump($k, $v, '=================');
		}

		return range(0,1);
	}

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
		$message  = sprintf('User generaged message @ %0.4f', microtime(true));

		$topic->produce(
			RD_KAFKA_PARTITION_UA
			, 0
			, $message
		);

		$producer->flush(500);

		return $message;
	}

	public function events($router)
	{
		header('Cache-Control: no-cache');
		header('Content-Type: text/event-stream');

		while(ob_get_level())
		{
			ob_end_flush();
			flush();
		}

		$events = [];

		return new EventSource(function() use($events) {

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

				// usleep(1 * 1000 * 1000);
			}
		});
	}
}
