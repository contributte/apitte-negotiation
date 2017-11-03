<?php

namespace Apitte\Negotiation\Transformer;

use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Core\Http\ApiResponse;
use Apitte\Negotiation\Http\AbstractEntity;

abstract class AbstractTransformer implements ITransformer
{

	/**
	 * @param ApiResponse $response
	 * @return AbstractEntity
	 */
	protected function getEntity(ApiResponse $response)
	{
		if (!$entity = $response->getEntity()) throw new InvalidStateException('Entity is required');

		return $entity;
	}

}
