<?php

namespace Apitte\Negotiation\Resolver;

use Apitte\Negotiation\ContentNegotiation;
use Exception;
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
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function resolveResponse(ServerRequestInterface $request, ResponseInterface $response)
	{
		return $this->negotiation->negotiate($request, $response);
	}

	/**
	 * @param Exception $exception
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function resolveException(Exception $exception, ServerRequestInterface $request, ResponseInterface $response)
	{
		return $this->negotiation->negotiate($request, $response, ['exception' => $exception]);
	}

}
