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
			if ($context['exception'] instanceof ClientErrorException || $context['exception'] instanceof ServerErrorException) {
				$response = $response->withStatus($context['exception']->getCode());
			} else {
				$response = $response->withStatus(500);
			}
		} else {
			// Convert data to array to json
			$content = Json::encode($this->extractData($request, $response, $context));
		}

		$response->getBody()->write($content);

		// Setup content type
		return $response
			->withHeader('Content-Type', 'application/json');
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
		$data = [];

		if ($exception instanceof ClientErrorException || $exception instanceof ServerErrorException) {
			$data['exception'] = $exception->getMessage();
			if ($exception->getContext() !== null) {
				$data['context'] = $exception->getContext();
			}
		} else {
			$data['exception'] = $this->debug ? $exception->getMessage() : 'Application encountered an internal error. Please try again later.';
		}

		return $data;
	}

}
