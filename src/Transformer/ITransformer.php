<?php

namespace Apitte\Negotiation\Transformer;

use Apitte\Mapping\Http\ApiRequest;
use Apitte\Mapping\Http\ApiResponse;

interface ITransformer
{

	/**
	 * Parse given data from request
	 *
	 * @param ApiRequest $request
	 * @param array $options
	 * @return ApiRequest
	 */
	public function decode(ApiRequest $request, array $options = []);

	/**
	 * Encode given data for response
	 *
	 * @param ApiResponse $response
	 * @param array $options
	 * @return ApiResponse
	 */
	public function encode(ApiResponse $response, array $options = []);

}
