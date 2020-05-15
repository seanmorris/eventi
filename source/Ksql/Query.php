<?php
namespace SeanMorris\Eventi\Ksql;

class Query extends Entity
{
	const DESCRIPTION = 'queryDescription';

	protected
		$id
		, $windowType
		, $executionPlan
		, $overriddenProperties
		, $ksqlHostQueryStatus
		, $queryType
		, $state
		, $type     = 'QUERY'
		, $fields   = []
		, $sources  = []
		, $sinks    = []
		, $topology = [];

	public function terminate()
	{
		return static::runQuery(spritnf(
			'TERMINATE `%s`'
			, str_replace('`', '``', $this->id)
		));
	}
}
