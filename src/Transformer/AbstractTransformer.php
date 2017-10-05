<?php

namespace Apitte\Negotiation\Transformer;

use Apitte\Mapping\Http\ApiRequest;
use Apitte\Mapping\Http\ApiResponse;

abstract class AbstractTransformer implements ITransformer
{

	/**
	 * @param ApiResponse $request
	 * @param array $options
	 * @return null
	 */
	public function encode(ApiResponse $request, array $options = [])
	{
		return NULL;
	}

	/**
	 * @param ApiRequest $request
	 * @param array $options
	 * @return null
	 */
	public function decode(ApiRequest $request, array $options = [])
	{
		return NULL;
	}

	/**
	 * @param ApiResponse $response
	 * @return bool
	 */
	protected function acceptResponse(ApiResponse $response)
	{
		return $response->getEntity() !== NULL;
	}

}
