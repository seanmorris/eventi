<?php
namespace SeanMorris\Eventi\Idilic\Route;
class RootRoute implements \SeanMorris\Ids\Routable
{
	protected $ksql;

	public function __construct()
	{
		$this->ksql = new \SeanMorris\Eventi\Ksql('http://ksql-server:8088/');
	}

	public function kqinfo()
	{
		return $this->ksql->info();
	}

	public function kqtables()
	{
		return $this->ksql->tables();
	}

	public function kqstreams()
	{
		return $this->ksql->streams();
	}

	public function kqqueries()
	{
		return $this->ksql->queries();
	}

	public function kqtable($router)
	{
		$args = $router->request()->path()->consumeNodes();

		return $this->ksql->table(...$args)->toStruct();
	}

	public function kqstream($router)
	{
		$args = $router->request()->path()->consumeNodes();

		return $this->ksql->stream(...$args)->toStruct();
	}

	public function kqquery($router)
	{
		$args = $router->request()->path()->consumeNodes();

		return $this->ksql->queries(...$args)->toStruct();
	}

	public function kqdropstream($router)
	{
		$args = $router->request()->path()->consumeNodes();

		return $this->ksql->dropStream(...$args)->toStruct();
	}

	public function kqdroptable($router)
	{
		$args = $router->request()->path()->consumeNodes();

		return $this->ksql->dropTable(...$args)->toStruct();
	}

	public function kqterminate($router)
	{
		$args = $router->request()->path()->consumeNodes();

		return $this->ksql->terminate(...$args)->toStruct();
	}

	public function kqstatus($router)
	{
		$args = $router->request()->path()->consumeNodes();

		return $this->ksql->status(...$args)->toStruct();
	}
}
