<?php

namespace Apitte\Negotiation\Decorator;

use Apitte\Core\Decorator\IDecorator;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\Negotiation\ContentNegotiation;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResponseEntityDecorator implements IDecorator
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
	public function decorate(ServerRequestInterface $request, ResponseInterface $response, array $context = [])
	{
		return $this->negotiation->negotiate($request, $response, $context);
	}

}
