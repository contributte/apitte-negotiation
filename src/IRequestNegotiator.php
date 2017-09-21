<?php

namespace Apitte\Negotiation;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface IRequestNegotiator
{

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ServerRequestInterface|NULL
	 */
	public function negotiateRequest(ServerRequestInterface $request, ResponseInterface $response);

}
