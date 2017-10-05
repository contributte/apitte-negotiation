<?php

namespace Apitte\Negotiation;

use Apitte\Mapping\Http\ApiRequest;
use Apitte\Mapping\Http\ApiResponse;

interface IResponseNegotiator
{

	/**
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @return ApiResponse|NULL
	 */
	public function negotiateResponse(ApiRequest $request, ApiResponse $response);

}
