<?php

namespace Apitte\Negotiation\Http;

use stdClass;

class ObjectEntity extends AbstractEntity
{

	/**
	 * @param stdClass $data
	 */
	public function __construct(stdClass $data)
	{
		parent::__construct($data);
	}

	/**
	 * @param stdClass $data
	 * @return static
	 */
	public static function from(stdClass $data)
	{
		return new static($data);
	}

}
