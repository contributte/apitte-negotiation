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
		// Skip if response is not our ApiResponse
		if (!($response instanceof ApiResponse)) return $response;

		// Skip if there's no entity and no $context, it does not make sence
		// to negotiate response without entity.
		// Except if there's exception in $context.
		if ($response->getEntity() === NULL && empty($context)) return $response;

		return $this->negotiation->negotiate($request, $response, $context);
	}

}
