<?php

namespace Apitte\Negotiation\Transformer;

use Apitte\Mapping\Http\ApiRequest;
use Apitte\Mapping\Http\ApiResponse;
use Apitte\Mapping\Http\ApiResponse as ApiMappingResponse;

abstract class AbstractTransformer implements ITransformer
{

	/**
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @param array $context
	 * @return null
	 */
	public function transform(ApiRequest $request, ApiResponse $response, array $context = [])
	{
		return NULL;
	}

	/**
	 * @param ApiResponse $response
	 * @return bool
	 */
	protected function accept(ApiResponse $response)
	{
		if (!($response instanceof ApiMappingResponse)) return FALSE;

		return $response->getEntity() !== NULL;
	}

}
