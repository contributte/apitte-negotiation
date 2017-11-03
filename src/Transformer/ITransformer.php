<?php

namespace Apitte\Negotiation\Transformer;

use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;

interface ITransformer
{

	/**
	 * Encode given data for response
	 *
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @param array $context
	 * @return ApiResponse
	 */
	public function transform(ApiRequest $request, ApiResponse $response, array $context = []);

}
