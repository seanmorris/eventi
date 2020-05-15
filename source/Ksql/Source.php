<?php
namespace SeanMorris\Eventi\Ksql;

abstract class Source extends Entity
{
	const DESCRIPTION = 'sourceDescription';

	protected
		$name
		, $type
		, $statement
		, $key
		, $timestamp
		, $serdes
		, $kafkaTopic
		, $extended
		, $statistics
		, $errorStats
		, $replication
		, $partitions
		, $schema       = []
		, $readQueries  = []
		, $writeQueries = [];

	public function drop()
	{
		[$response] = $this->_ksql::runQuery(sprintf(
			'DROP %s `%s`'
			, $this->type
			, str_replace('`', '``', $this->name)
		));

		if($response->error_code ?? 0)
		{
			return new \SeanMorris\Eventi\Ksql\Error(
				$this, $response
			);
		}

		return $response;
	}
}
