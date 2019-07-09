<?php declare(strict_types = 1);

namespace Apitte\Negotiation\Decorator;

use Apitte\Core\Decorator\IResponseDecorator;
use Apitte\Core\ErrorHandling\ErrorConverter;
use Apitte\Core\Exception\ApiException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\Negotiation\ContentNegotiation;

class ResponseEntityDecorator implements IResponseDecorator, ErrorConverter
{

	/** @var ContentNegotiation */
	private $negotiation;

	/** @var bool */
	private $transformedError = false;

	/** @var ErrorConverter */
	private $fallbackErrorConverter;

	public function __construct(ContentNegotiation $negotiation, ErrorConverter $fallbackErrorConverter)
	{
		$this->negotiation = $negotiation;
		$this->fallbackErrorConverter = $fallbackErrorConverter;
	}

	public function createResponseFromError(ApiException $error, ?ApiRequest $request = null, ?ApiResponse $response = null): ApiResponse
	{
		$this->transformedError = true;

		// Error was thrown outside of a scope where is request and response available, fallback to a default (json) converter
		if ($request === null || $response === null) {
			return $this->fallbackErrorConverter->createResponseFromError($error);
		}

		return $this->negotiation->negotiate($request, $response, ['exception' => $error]);
	}

	public function decorateResponse(ApiRequest $request, ApiResponse $response): ApiResponse
	{
		// Negotiations already called through error converter, does not make sense to call twice
		if ($this->transformedError) {
			$this->transformedError = false;
			return $response;
		}

		// Cannot negotiate response without entity
		if ($response->getEntity() === null) {
			return $response;
		}

		return $this->negotiation->negotiate($request, $response);
	}

}
