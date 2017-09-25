<?php

namespace Apitte\Negotiation\Http;

class CsvStream extends ArrayStream
{

	/** @var string[] */
	private $header = [];

	/** @var string[] */
	private $rows = [];

	/**
	 * @param string[] $header
	 * @return static
	 */
	public function withHeader(array $header)
	{
		$this->header = $header;
		$this->update();

		return $this;
	}

	/**
	 * @param string[] $rows
	 * @return static
	 */
	public function withRows(array $rows)
	{
		$this->rows = $rows;
		$this->update();

		return $this;
	}

	/**
	 * @return void
	 */
	private function update()
	{
		$this->with(empty($this->header) ? $this->rows : array_merge([$this->header], $this->rows));
	}

}
