<?php

namespace Apitte\Negotiation\Transformer;

use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Mapping\Http\ApiRequest;
use Apitte\Mapping\Http\ApiResponse;
use Nette\DI\Container;

class CallbackTransformer extends AbstractTransformer
{

	/** @var Container */
	protected $container;

	/**
	 * @param Container $container
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Encode given data for response
	 *
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @param array $context
	 * @return ApiResponse
	 */
	public function transform(ApiRequest $request, ApiResponse $response, array $context = [])
	{
		// Return immediately if response is not accepted
		if (!$this->accept($response)) return $response;

		// Return immediately if context hasn't defined callback
		if (!isset($context['callback'])) return $response;

		// Fetch service
		$service = $this->container->getByType($context['callback']);

		if (!is_callable($service)) {
			throw new InvalidStateException(sprintf('Callback "%s" must implement __invoke() method', $context['callback']));
		}

		return $service($request, $response);
	}

}
