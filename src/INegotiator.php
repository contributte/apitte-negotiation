<?php

namespace Apitte\Negotiation;

use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;

interface INegotiator
{

	const RENDERER = '#';
	const FALLBACK = '*';

	/**
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @param array $context
	 * @return ApiResponse|NULL
	 */
	public function negotiate(ApiRequest $request, ApiResponse $response, array $context = []);

}
