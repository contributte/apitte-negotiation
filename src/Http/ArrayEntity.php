<?php

namespace Apitte\Negotiation\Http;

class ArrayEntity extends AbstractEntity
{

	/**
	 * @param array $data
	 */
	public function __construct(array $data)
	{
		parent::__construct($data);
	}

	/**
	 * @param array $data
	 * @return static
	 */
	public static function from(array $data)
	{
		return new static($data);
	}

}
