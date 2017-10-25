<?php

namespace Apitte\Negotiation;

use Apitte\Mapping\Http\ApiRequest;
use Apitte\Mapping\Http\ApiResponse;

interface INegotiator
{

	/**
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @param array $context
	 * @return ApiResponse|NULL
	 */
	public function negotiate(ApiRequest $request, ApiResponse $response, array $context = []);

}
