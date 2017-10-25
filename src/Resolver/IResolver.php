<?php

namespace Apitte\Negotiation\Resolver;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface IResolver
{

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function resolveResponse(ServerRequestInterface $request, ResponseInterface $response);

	/**
	 * @param Exception $exception
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function resolveException(Exception $exception, ServerRequestInterface $request, ResponseInterface $response);

}
