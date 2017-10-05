<?php

namespace Apitte\Negotiation\Transformer;

use Apitte\Mapping\Http\ApiRequest;
use Apitte\Mapping\Http\ApiResponse;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class JsonTransformer extends AbstractTransformer
{

	/**
	 * Encode given data for response
	 *
	 * @param ApiResponse $response
	 * @param array $options
	 * @return ApiResponse
	 */
	public function encode(ApiResponse $response, array $options = [])
	{
		// Return immediately if response is not accepted
		if (!$this->acceptResponse($response)) return $response;

		// Convert data to array to json
		$content = Json::encode($response->getEntity()->toArray());
		$response->getBody()->write($content);

		// Setup content type
		$response = $response
			->withHeader('Content-Type', 'application/json');

		return $response;
	}

	/**
	 * Parse given data from request
	 *
	 * @param ApiRequest $request
	 * @param array $options
	 * @return ApiRequest
	 */
	public function decode(ApiRequest $request, array $options = [])
	{
		try {
			// Try to decode pure JSON in body and set to parse body
			$body = clone $request->getBody();
			$request = $request->withParsedBody(Json::decode((string) $body->getContents(), JSON_OBJECT_AS_ARRAY));
		} catch (JsonException $e) {
			// Just catch exception
		}

		return $request;
	}

}
