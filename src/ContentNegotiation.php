<?php

namespace Apitte\Negotiation;

use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;

class ContentNegotiation
{

	// Attributes in ApiRequest
	const ATTR_SKIP = 'apitte.negotiation.skip';

	/** @var INegotiator[] */
	protected $negotiators = [];

	/**
	 * @param array $negotiators
	 * @param array $options
	 */
	public function __construct(array $negotiators = [], array $options = [])
	{
		$this->addNegotiations($negotiators);
	}

	/**
	 * SETTERS *****************************************************************
	 */

	/**
	 * @param INegotiator[] $negotiators
	 * @return void
	 */
	public function addNegotiations(array $negotiators)
	{
		foreach ($negotiators as $negotiator) {
			$this->negotiators[] = $negotiator;
		}
	}

	/**
	 * API *********************************************************************
	 */

	/**
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @param array $context
	 * @return ApiResponse
	 */
	public function negotiate(ApiRequest $request, ApiResponse $response, array $context = [])
	{
		// Should we skip negotiation?
		if ($request->getAttribute(self::ATTR_SKIP, FALSE) === TRUE) return $response;

		// Validation
		if (!$this->negotiators) {
			throw new InvalidStateException('At least one response negotiator is required');
		}

		foreach ($this->negotiators as $negotiator) {
			// Pass to negotiator and check return value
			$negotiated = $negotiator->negotiate($request, $response, $context);

			// If it's not NULL, we have an ApiResponse
			if ($negotiated !== NULL) return $negotiated;
		}

		return $response;
	}

}
