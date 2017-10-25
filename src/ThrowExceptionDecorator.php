<?php

namespace Apitte\Negotiation;

use Apitte\Mapping\Decorator\IExceptionDecorator;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ThrowExceptionDecorator implements IExceptionDecorator
{

	/**
	 * @param Exception $exception
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface|void
	 */
	public function decorateException(Exception $exception, ServerRequestInterface $request, ResponseInterface $response)
	{
		throw $exception;
	}

}
