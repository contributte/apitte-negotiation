<?php

namespace Apitte\Negotiation\Http;

abstract class AbstractEntity
{

	/** @var mixed */
	protected $data = [];

	/**
	 * @param mixed $data
	 */
	public function __construct($data)
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
	 */
	public function withData($data)
	{
		$this->data = $data;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return (array) $this->getData();
	}

}
