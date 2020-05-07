<?php
namespace SeanMorris\Eventi;
class Event
{
	protected $id, $message;

	public function __construct($message = NULL, $id = NULL)
	{
		$this->message = $message;
		$this->id      = $id;
	}

	public function toString()
	{
		$format = "event: %s\ndata: %s\n";

		if($this->id)
		{
			$format .= "id: %s\n";
		}

		$format .= "\n";

		return sprintf(
			$format
			, 'ServerEvent'
			, json_encode($this->message)
			, $this->id
		);
	}

	public function __toString()
	{
		return $this->toString();
	}
}
