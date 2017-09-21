<?php

namespace Apitte\Negotiation\Transformer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CsvTransformer extends AbstractTransformer
{

	/**
	 * Encode given data for response
	 *
	 * @param ResponseInterface $response
	 * @param array $options
	 * @return void
	 */
	public function encode(ResponseInterface $response, array $options = [])
	{
		// Need implement
	}

	/**
	 * Parse given data from request
	 *
	 * @param ServerRequestInterface $request
	 * @param array $options
	 * @return void
	 */
	public function decode(ServerRequestInterface $request, array $options = [])
	{
		// Need implement
	}

}
