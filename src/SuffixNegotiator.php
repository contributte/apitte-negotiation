<?php

namespace Apitte\Negotiation;

use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Negotiation\Transformer\ITransformer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SuffixNegotiator implements IResponseNegotiator, IRequestNegotiator
{

	// Masks
	const FALLBACK = '*';

	// Attributes in ServerRequestInterface
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
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ServerRequestInterface
	 */
	public function negotiateRequest(ServerRequestInterface $request, ResponseInterface $response)
	{
		if (!$this->transformers) {
			throw new InvalidStateException('Please add at least one transformer');
		}

		$path = $request->getUri()->getPath();

		foreach ($this->transformers as $suffix => $transformer) {
			// Skip fallback transformer
			if ($suffix == self::FALLBACK) continue;

			// Normalize suffix
			$suffix = sprintf('.%s', ltrim($suffix, '.'));

			// Try match by suffix
			if ($this->match($path, $suffix) === TRUE) {
				// Strip suffix from URL
				$newPath = substr($path, 0, strlen($path) - strlen($suffix));

				// Update ApiRequest without suffix (.json, ...)
				// and also fill request attribute
				$request = $request
					->withUri($request->getUri()->withPath($newPath))
					->withAttribute(self::ATTR_SUFFIX, $suffix);

				// Try to transform current request body
				// only in POST/PUT method
				if (in_array($request->getMethod(), ['POST', 'PUT'])) {
					($transformed = $this->transformIn($transformer, $request, $response));
					$request = $transformed ?: $request;
				}

				return $request;
			}
		}

		return $request;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function negotiateResponse(ServerRequestInterface $request, ResponseInterface $response)
	{
		if (!$this->transformers) {
			throw new InvalidStateException('Please add at least one transformer');
		}

		$requestSuffix = $request->getAttribute(self::ATTR_SUFFIX);

		foreach ($this->transformers as $suffix => $transformer) {
			// Skip fallback transformer
			if ($suffix == self::FALLBACK) continue;

			// Normalize suffix
			$suffix = sprintf('.%s', ltrim($suffix, '.'));

			// Try match by suffix
			if ($requestSuffix === $suffix) {
				return $this->transformOut($transformer, $request, $response);
			}
		}

		// Try fallback
		if (isset($this->transformers[self::FALLBACK])) {
			// Transform (fallback) data to given format
			return $this->transformOut($this->transformers[self::FALLBACK], $request, $response);
		}

		return $response;
	}

	/**
	 * HELPERS *****************************************************************
	 */

	/**
	 * @param ITransformer $transformer
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	protected function transformOut(ITransformer $transformer, ServerRequestInterface $request, ResponseInterface $response)
	{
		return $transformer->encode($response);
	}

	/**
	 * @param ITransformer $transformer
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ServerRequestInterface
	 */
	protected function transformIn(ITransformer $transformer, ServerRequestInterface $request, ResponseInterface $response)
	{
		return $transformer->decode($request);
	}

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
