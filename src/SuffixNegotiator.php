<?php

namespace Apitte\Negotiation;

use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Mapping\Http\ApiRequest;
use Apitte\Mapping\Http\ApiResponse;
use Apitte\Negotiation\Transformer\ITransformer;

class SuffixNegotiator implements INegotiator
{

	// Masks
	const FALLBACK = '*';

	// Attributes in ApiRequest
	const ATTR_SUFFIX = 'apitte.negotiation.suffix';

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

		foreach ($this->transformers as $suffix => $transformer) {
			// Skip fallback transformer
			if ($suffix == self::FALLBACK) continue;

			// Normalize suffix
			$suffix = sprintf('.%s', ltrim($suffix, '.'));

			// Try match by suffix
			if ($this->match($request->getUri()->getPath(), $suffix)) {
				return $transformer->transform($request, $response, $context);
			}
		}

		// Try fallback
		if (isset($this->transformers[self::FALLBACK])) {
			// Transform (fallback) data to given format
			return $this->transformers[self::FALLBACK]->transform($request, $response, $context);
		}

		return $response;
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
