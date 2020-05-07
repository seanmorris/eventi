<?php
namespace SeanMorris\Eventi;
class HomeRoute implements \SeanMorris\Ids\Routable
{
	public function events($router)
	{
		header('Cache-Control: no-cache');
		header('Content-Type: text/event-stream');

		return new EventSource(function() {

			$messages = rand(5, 15);

			$id = NULL;

			while(TRUE)
			{
				\SeanMorris\Ids\Log::error('WTF', connection_aborted());

				if(connection_aborted())
				{
					break;
				}

				ob_start();

				$id = sprintf(
					'%0.6f-%s'
					, microtime(true)
					, uniqid()
				);

				yield new Event('Ping!!!', $id);

				while(ob_get_level())
				{
					ob_end_flush();
					flush();
				}

				usleep(1 * 1000 * 1000);
			}
		});
	}
}
