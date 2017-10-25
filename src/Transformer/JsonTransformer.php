<?php

namespace Apitte\Negotiation\Transformer;

use Apitte\Mapping\Http\ApiRequest;
use Apitte\Mapping\Http\ApiResponse;
use Nette\Utils\Json;

class JsonTransformer extends AbstractTransformer
{

	/**
	 * Encode given data for response
	 *
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @param array $context
	 * @return ApiResponse
	 */
	public function transform(ApiRequest $request, ApiResponse $response, array $context = [])
	{
		// Return immediately if response is not accepted
		if (!$this->accept($response)) return $response;

		// Convert data to array to json
		$content = Json::encode($response->getEntity()->toArray());
		$response->getBody()->write($content);

		// Setup content type
		$response = $response
			->withHeader('Content-Type', 'application/json');

		return $response;
	}

}
