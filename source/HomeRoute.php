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

	public function events($router)
	{
		header('Cache-Control: no-cache');
		header('Content-Type: text/event-stream');

		$events = [];

		KayVeeConsumer::listen('test', function($event) use($events) {

			array_push($events);

		});

		return new EventSource(function() use($events) {

			$messages = rand(5, 15);

			$id = NULL;

			while(TRUE)
			{
				\SeanMorris\Ids\Log::error('tick...', connection_aborted());

				if(connection_aborted())
				{
					break;
				}


				// $id = sprintf(
				// 	'%0.6f-%s'
				// 	, microtime(true)
				// 	, uniqid()
				// );

				if($events)
				{
					ob_start();

					yield new Event(
						$message->payload,
						$message->offset
					);

					while(ob_get_level())
					{
						ob_end_flush();
						flush();
					}
				}

				// yield new Event('Ping!!!', $id);


				usleep(1 * 1000 * 1000);
			}
		});
	}
}
