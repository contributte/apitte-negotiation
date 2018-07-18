<?php declare(strict_types = 1);

namespace Apitte\Negotiation\Http;

use stdClass;

class ObjectEntity extends AbstractEntity
{

	public function __construct(stdClass $data)
	{
		parent::__construct($data);
	}

	public static function from(stdClass $data): self
	{
		return new static($data);
	}

}
