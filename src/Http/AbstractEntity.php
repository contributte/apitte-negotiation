<?php

namespace Apitte\Negotiation\Http;

abstract class AbstractEntity
{

	/** @var mixed */
	protected $data;

	/**
	 * @param mixed $data
	 */
	public function __construct($data = NULL)
	{
		$this->data = $data;
	}

	/**
	 * @return mixed
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @param mixed $data
	 * @return void
	 */
	protected function setData($data)
	{
		$this->data = $data;
	}

}
