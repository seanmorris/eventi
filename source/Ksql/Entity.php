<?php
namespace SeanMorris\Eventi\Ksql;

abstract class Entity
{
	protected $_ksql  = null;

	const DESCRIPTION = null;

	public function __construct($ksql, $skeleton)
	{
		$this->_ksql = $ksql;

		if(isset($skeleton->statistics))
		{
			$skeleton->statistics = static::parseStats($skeleton->statistics);
		}

		if(isset($skeleton->errorStats))
		{
			$skeleton->errorStats = static::parseStats($skeleton->errorStats);
		}

		foreach($this as $property => $value)
		{
			if($property[0] === '_')
			{
				continue;
			}

			if(isset($skeleton->{ $property }))
			{
				$this->{ $property } = $skeleton->{ $property };
			}
		}
	}

	public function __get($name)
	{
		return $this->{ $name };
	}

	public function toStruct()
	{
		$skeleton = [];

		foreach($this as $property => $value)
		{
			if($property[0] === '_')
			{
				continue;
			}

			$skeleton[ $property ] = $value;
		}

		return $skeleton;
	}

	protected static function parseStats($statistics)
	{
		$result  = [];
		$statsStrings = preg_split('/\:?\s+/', $statistics);

		foreach($statsStrings as $i => $s)
		{
			if($i % 2 === 1)
			{
				$result[ $statsStrings[ $i-1 ] ] = $s;
			}
		}

		return $result;
	}
}
