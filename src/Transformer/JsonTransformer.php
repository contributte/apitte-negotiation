<?php declare(strict_types = 1);

namespace Apitte\Negotiation\Transformer;

use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Nette\Utils\Json;

class JsonTransformer extends AbstractTransformer
{

	/**
	 * Encode given data for response
	 *
	 * @param mixed[] $context
	 */
	public function transform(ApiRequest $request, ApiResponse $response, array $context = []): ApiResponse
	{
		if (isset($context['exception'])) {
			// Convert exception to json
			$content = Json::encode($this->extractException($request, $response, $context));
		} else {
			// Convert data to array to json
			$content = Json::encode($this->extractData($request, $response, $context));
		}

		$response->getBody()->write($content);

		// Setup content type
		$response = $response
			->withHeader('Content-Type', 'application/json');

		return $response;
	}

	/**
	 * @param mixed[] $context
	 * @return mixed
	 */
	protected function extractData(ApiRequest $request, ApiResponse $response, array $context)
	{
		return $this->getEntity($response)->getData();
	}

	/**
	 * @param mixed[] $context
	 * @return mixed[]
	 */
	protected function extractException(ApiRequest $request, ApiResponse $response, array $context): array
	{
		$exception = $context['exception'];
		$data = ['exception' => $exception->getMessage()];

		if ($exception instanceof ClientErrorException) {
			$data['context'] = $exception->getContext();
		}

		if ($exception instanceof ServerErrorException) {
			$data['context'] = $exception->getContext();
		}

		return $data;
	}

}
