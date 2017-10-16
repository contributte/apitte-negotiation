<?php

namespace Apitte\Negotiation;

use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Mapping\Http\ApiRequest;
use Apitte\Mapping\Http\ApiResponse;
use Apitte\Negotiation\Http\ArrayEntity;
use Exception;

class ContentNegotiation
{

	// Attributes in ApiRequest
	const ATTR_SKIP = 'apitte.negotiation.skip';
	const ATTR_SKIP_REQUEST = 'apitte.negotiation.skip.request';
	const ATTR_SKIP_RESPONSE = 'apitte.negotiation.skip.response';
	const ATTR_SKIP_EXCEPTION = 'apitte.negotiation.skip.exception';

	/** @var IRequestNegotiator[] */
	protected $requestNegotiators = [];

	/** @var IResponseNegotiator[] */
	protected $responseNegotiators = [];

	/** @var bool */
	protected $catchException = TRUE;

	/**
	 * @param array $negotiators
	 * @param array $options
	 */
	public function __construct(array $negotiators = [], array $options = [])
	{
		$this->addNegotiations($negotiators);
		$this->parseOptions($options);
	}

	/**
	 * SETTERS *****************************************************************
	 */

	/**
	 * @param bool $catch
	 * @return void
	 */
	public function setCatchException($catch = TRUE)
	{
		$this->catchException = boolval($catch);
	}

	/**
	 * @param IRequestNegotiator[] $negotiators
	 * @return void
	 */
	public function addRequestNegotiations(array $negotiators)
	{
		foreach ($negotiators as $negotiator) {
			$this->addRequestNegotiation($negotiator);
		}
	}

	/**
	 * @param IRequestNegotiator $negotiator
	 * @return void
	 */
	public function addRequestNegotiation(IRequestNegotiator $negotiator)
	{
		$this->requestNegotiators[] = $negotiator;
	}

	/**
	 * @param IResponseNegotiator[] $negotiators
	 * @return void
	 */
	public function addResponseNegotiations(array $negotiators)
	{
		foreach ($negotiators as $negotiator) {
			$this->addResponseNegotiation($negotiator);
		}
	}

	/**
	 * @param IResponseNegotiator $negotiator
	 * @return void
	 */
	public function addResponseNegotiation(IResponseNegotiator $negotiator)
	{
		$this->responseNegotiators[] = $negotiator;
	}

	/**
	 * @param array $negotiators
	 * @return void
	 */
	public function addNegotiations(array $negotiators)
	{
		foreach ($negotiators as $negotiator) {
			if ($negotiator instanceof IRequestNegotiator) {
				$this->addRequestNegotiation($negotiator);
			}
			if ($negotiator instanceof IResponseNegotiator) {
				$this->addResponseNegotiation($negotiator);
			}
		}
	}

	/**
	 * @param array $options
	 * @return void
	 */
	public function parseOptions(array $options)
	{
		if (isset($options['catch'])) {
			$this->setCatchException($options['catch']);
		}
	}

	/**
	 * API *********************************************************************
	 */

	/**
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @return ApiRequest
	 */
	public function negotiateRequest(ApiRequest $request, ApiResponse $response)
	{
		// Should we skip negotiation?
		if ($request->getAttribute(self::ATTR_SKIP, FALSE) === TRUE) return $request;
		if ($request->getAttribute(self::ATTR_SKIP_REQUEST, FALSE) === TRUE) return $request;

		// Validation
		if (!$this->requestNegotiators) {
			throw new InvalidStateException('At least one request negotiator is required');
		}

		foreach ($this->requestNegotiators as $negotiator) {
			// Pass to negotiator and check return value
			$negotiated = $negotiator->negotiateRequest($request, $response);

			// If it's not NULL, we have an ApiRequest
			if ($negotiated !== NULL) return $negotiated;
		}

		return $request;
	}

	/**
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @return ApiResponse
	 */
	public function negotiateResponse(ApiRequest $request, ApiResponse $response)
	{
		// Should we skip negotiation?
		if ($request->getAttribute(self::ATTR_SKIP, FALSE) === TRUE) return $response;
		if ($request->getAttribute(self::ATTR_SKIP_RESPONSE, FALSE) === TRUE) return $response;

		// Validation
		if (!$this->responseNegotiators) {
			throw new InvalidStateException('At least one response negotiator is required');
		}

		foreach ($this->responseNegotiators as $negotiator) {
			// Pass to negotiator and check return value
			$negotiated = $negotiator->negotiateResponse($request, $response);

			// If it's not NULL, we have an ApiResponse
			if ($negotiated !== NULL) return $negotiated;
		}

		return $response;
	}

	/**
	 * @param Exception $exception
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @return ApiResponse
	 */
	public function negotiateException(Exception $exception, ApiRequest $request, ApiResponse $response)
	{
		// Should we skip negotiation?
		if ($request->getAttribute(self::ATTR_SKIP, FALSE) === TRUE) return $response;
		if ($request->getAttribute(self::ATTR_SKIP_EXCEPTION, FALSE) === TRUE) return $response;

		// Throw or catch exception?
		if ($this->catchException === FALSE) throw $exception;

		// Return response if entity is already setup
		if ($response->getEntity() && $response->getEntity()->getData()) return $response;

		$code = $exception->getCode();

		$response = $response
			->withEntity(new ArrayEntity([
				'error' => $exception->getMessage(),
				'code' => $exception->getCode(),
			]))->withStatus($code < 200 || $code > 504 ? 404 : $code);

		return $response;
	}

}
