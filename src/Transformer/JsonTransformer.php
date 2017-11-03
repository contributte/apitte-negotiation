<?php

namespace Apitte\Negotiation\Transformer;

use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
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
		if (isset($context['exception'])) {
			// Convert exception to json
			$content = Json::encode(['exception' => $context['exception']->getMessage()]);
		} else {
			// Convert data to array to json
			$content = Json::encode($this->getEntity($response)->getData());
		}

		$response->getBody()->write($content);

		// Setup content type
		$response = $response
			->withHeader('Content-Type', 'application/json');

		return $response;
	}

}
