<?php declare(strict_types = 1);

namespace Apitte\Negotiation\Transformer;

use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Core\Http\ApiResponse;
use Apitte\Negotiation\Http\AbstractEntity;

abstract class AbstractTransformer implements ITransformer
{

	/** @var bool */
	protected $debug = false;

	protected function getEntity(ApiResponse $response): AbstractEntity
	{
		$entity = $response->getEntity();
		if ($entity === null) {
			throw new InvalidStateException('Entity is required');
		}

		return $entity;
	}

	public function setDebugMode(bool $debug): void
	{
		$this->debug = $debug;
	}

}
