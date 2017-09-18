<?php

namespace Apitte\Core\Middlewares\Negotiation;

use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;

interface IRequestNegotiator
{

	/**
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @return ApiRequest|NULL
	 */
	public function negotiateRequest(ApiRequest $request, ApiResponse $response);

}
