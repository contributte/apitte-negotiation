<?php

namespace Apitte\Negotiation\Transformer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ITransformer
{

	/**
	 * Parse given data from request
	 *
	 * @param ServerRequestInterface $request
	 * @param array $options
	 * @return ServerRequestInterface
	 */
	public function decode(ServerRequestInterface $request, array $options = []);

	/**
	 * Encode given data for response
	 *
	 * @param ResponseInterface $response
	 * @param array $options
	 * @return ResponseInterface
	 */
	public function encode(ResponseInterface $response, array $options = []);

}
