<?php

namespace Apitte\Negotiation;

use Apitte\Mapping\Http\ApiRequest;
use Apitte\Mapping\Http\ApiResponse;

interface IRequestNegotiator
{

	/**
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @return ApiResponse|NULL
	 */
	public function negotiateRequest(ApiRequest $request, ApiResponse $response);

}
