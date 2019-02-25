<?php declare(strict_types = 1);

namespace Apitte\Negotiation\Decorator;

use Apitte\Core\Decorator\IErrorDecorator;
use Apitte\Core\Decorator\IResponseDecorator;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\Negotiation\ContentNegotiation;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class ResponseEntityDecorator implements IResponseDecorator, IErrorDecorator
{

	/** @var ContentNegotiation */
	private $negotiation;

	public function __construct(ContentNegotiation $negotiation)
	{
		$this->negotiation = $negotiation;
	}

	/**
	 * @param ApiRequest $request
	 */
	public function decorateError(ServerRequestInterface $request, ResponseInterface $response, Throwable $error): ResponseInterface
	{
		// Skip if response is not our ApiResponse
		if (!($response instanceof ApiResponse)) {
			return $response;
		}

		return $this->negotiation->negotiate($request, $response, ['exception' => $error]);
	}

	/**
	 * @param ApiRequest $request
	 */
	public function decorateResponse(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		// Skip if response is not our ApiResponse
		if (!($response instanceof ApiResponse)) {
			return $response;
		}

		// Cannot negotiate response without entity
		if ($response->getEntity() === null) {
			return $response;
		}

		return $this->negotiation->negotiate($request, $response);
	}

}
