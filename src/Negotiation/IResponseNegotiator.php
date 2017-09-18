<?php

namespace Apitte\Core\Middlewares\Negotiation;

use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;

interface IResponseNegotiator
{

	/**
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @return ApiResponse|NULL
	 */
	public function negotiateResponse(ApiRequest $request, ApiResponse $response);

}
