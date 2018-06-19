<?php declare(strict_types = 1);

namespace Apitte\Negotiation\Decorator;

use Apitte\Core\Decorator\IDecorator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ThrowExceptionDecorator implements IDecorator
{

	/**
	 * @param mixed[] $context
	 */
	public function decorate(ServerRequestInterface $request, ResponseInterface $response, array $context = []): ResponseInterface
	{
		if (isset($context['exception'])) {
			throw $context['exception'];
		}

		return $response;
	}

}
