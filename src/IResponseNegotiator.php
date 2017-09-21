<?php

namespace Apitte\Negotiation;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface IResponseNegotiator
{

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface|NULL
	 */
	public function negotiateResponse(ServerRequestInterface $request, ResponseInterface $response);

}
