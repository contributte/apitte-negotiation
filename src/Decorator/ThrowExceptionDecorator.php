<?php

namespace Apitte\Negotiation\Decorator;

use Apitte\Core\Decorator\IDecorator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ThrowExceptionDecorator implements IDecorator
{

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @param array $context
	 * @return ResponseInterface
	 */
	public function decorate(ServerRequestInterface $request, ResponseInterface $response, array $context = [])
	{
		if (isset($context['exception'])) {
			throw $context['exception'];
		}

		return $response;
	}

}
