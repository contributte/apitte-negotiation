<?php

namespace Apitte\Negotiation;

use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\Negotiation\Transformer\ITransformer;

class SuffixNegotiator implements INegotiator
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

		// Early return if there's no endpoint
		if (!($endpoint = $response->getEndpoint())) return NULL;

		// Get negotiations
		$negotiations = $endpoint->getNegotiations();

		// Try match by allowed negotiations
		foreach ($negotiations as $negotiation) {
			// Normalize suffix
			$suffix = sprintf('.%s', ltrim($negotiation->getSuffix(), '.'));

			// Try match by suffix
			if ($this->match($request->getUri()->getPath(), $suffix)) {
				$transformer = ltrim($suffix, '.');

				// If callback is defined -> process to callback transformer
				if ($negotiation->getRenderer()) {
					$transformer = INegotiator::RENDERER;
					$context['renderer'] = $negotiation->getRenderer();
				}

				if (!isset($this->transformers[$transformer])) {
					throw new InvalidStateException(sprintf('Transformer "%s" not registered', $transformer));
				}

				return $this->transformers[$transformer]->transform($request, $response, $context);
			}
		}

		return NULL;
	}

	/**
	 * HELPERS *****************************************************************
	 */

	/**
	 * Match transformer for the suffix? (.json?)
	 *
	 * @param string $path
	 * @param string $suffix
	 * @return bool
	 */
	private function match($path, $suffix)
	{
		return substr($path, -strlen($suffix)) === $suffix;
	}

}
