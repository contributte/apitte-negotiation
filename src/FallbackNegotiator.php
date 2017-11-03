<?php

namespace Apitte\Negotiation;

use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\Negotiation\Transformer\ITransformer;

class FallbackNegotiator implements INegotiator
{

	/** @var ITransformer */
	protected $transformer;

	/**
	 * @param ITransformer $transformer
	 */
	public function __construct(ITransformer $transformer)
	{
		$this->transformer = $transformer;
	}

	/**
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @param array $context
	 * @return ApiResponse
	 */
	public function negotiate(ApiRequest $request, ApiResponse $response, array $context = [])
	{
		return $this->transformer->transform($request, $response, $context);
	}

}
