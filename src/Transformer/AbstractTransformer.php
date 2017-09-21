<?php

namespace Apitte\Negotiation\Transformer;

use Apitte\Negotiation\Http\ArrayStream;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractTransformer implements ITransformer
{

	/**
	 * @param ResponseInterface $response
	 * @return bool
	 */
	protected function acceptResponse(ResponseInterface $response)
	{
		return $response->getBody() instanceof ArrayStream;
	}

}
