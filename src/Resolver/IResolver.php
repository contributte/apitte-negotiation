<?php

namespace Apitte\Negotiation\Resolver;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface IResolver
{

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @param array $context
	 * @return ResponseInterface
	 */
	public function resolve(ServerRequestInterface $request, ResponseInterface $response, array $context = []);

}
