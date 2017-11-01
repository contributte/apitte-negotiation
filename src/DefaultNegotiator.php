<?php

namespace Apitte\Negotiation;

use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Mapping\Http\ApiRequest;
use Apitte\Mapping\Http\ApiResponse;
use Apitte\Negotiation\Transformer\ITransformer;

class DefaultNegotiator implements INegotiator
{

	/** @var ITransformer[] */
	private $transformers = [];

	/**
	 * @param ITransformer[] $transformers
	 */
	public function __construct(array $transformers)
	{
		$this->addTransformers($transformers);
	}

	/**
	 * GETTERS/SETTERS *********************************************************
	 */

	/**
	 * @param ITransformer[] $transformers
	 * @return void
	 */
	private function addTransformers(array $transformers)
	{
		foreach ($transformers as $suffix => $transformer) {
			$this->addTransformer($suffix, $transformer);
		}
	}

	/**
	 * @param string $suffix
	 * @param ITransformer $transformer
	 * @return void
	 */
	private function addTransformer($suffix, ITransformer $transformer)
	{
		$this->transformers[$suffix] = $transformer;
	}

	/**
	 * NEGOTIATION *************************************************************
	 */

	/**
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @param array $context
	 * @return ApiResponse
	 */
	public function negotiate(ApiRequest $request, ApiResponse $response, array $context = [])
	{
		if (!$this->transformers) {
			throw new InvalidStateException('Please add at least one transformer');
		}

		// Validate that we have an endpoint
		if (!($endpoint = $response->getEndpoint())) {
			throw new InvalidStateException('Endpoint is required');
		}

		$negotiations = $endpoint->getNegotiations();

		// Try default
		foreach ($negotiations as $negotiation) {
			// Skip non default negotiations
			if (!$negotiation->isDefault()) continue;

			// Normalize suffix for transformer
			$transformer = ltrim($negotiation->getSuffix(), '.');

			// If callback is defined -> process to callback transformer
			if ($negotiation->getCallback()) {
				$transformer = INegotiator::CALLBACK;
				$context['callback'] = $negotiation->getCallback();
			}

			// Try default negotiation
			if (!isset($this->transformers[$transformer])) {
				throw new InvalidStateException(sprintf('Transformer "%s" not registered', $transformer));
			}

			// Transform (fallback) data to given format
			return $this->transformers[$transformer]->transform($request, $response, $context);
		}

		return NULL;
	}

}
