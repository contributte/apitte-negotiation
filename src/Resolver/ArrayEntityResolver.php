<?php

namespace Apitte\Negotiation\Resolver;

use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\Negotiation\ContentNegotiation;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ArrayEntityResolver implements IResolver
{

	/** @var ContentNegotiation */
	private $negotiation;

	/**
	 * @param ContentNegotiation $negotiation
	 */
	public function __construct(ContentNegotiation $negotiation)
	{
		$this->negotiation = $negotiation;
	}

	/**
	 * @param ApiRequest|ServerRequestInterface $request
	 * @param ApiResponse|ResponseInterface $response
	 * @param array $context
	 * @return ResponseInterface
	 */
	public function resolve(ServerRequestInterface $request, ResponseInterface $response, array $context = [])
	{
		return $this->negotiation->negotiate($request, $response, $context);
	}

}
