<?php declare(strict_types = 1);

namespace Apitte\Negotiation\Transformer;

use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\Core\Http\ResponseAttributes;
use Apitte\Negotiation\Http\ArrayEntity;
use Nette\Utils\Json;
use Throwable;

class JsonUnifyTransformer extends AbstractTransformer
{

	// Statuses
	public const
		STATUS_SUCCESS = 'success',
		STATUS_ERROR = 'error';

	/**
	 * Encode given data for response
	 *
	 * @param mixed[] $context
	 */
	public function transform(ApiRequest $request, ApiResponse $response, array $context = []): ApiResponse
	{
		if (isset($context['exception'])) {
			return $this->transformException($context['exception'], $request, $response);
		}

		return $this->transformResponse($request, $response);
	}

	protected function transformException(Throwable $exception, ApiRequest $request, ApiResponse $response): ApiResponse
	{
		// Unify response
		$response = $this->unifyException($exception, $request, $response);

		// Convert data to array to json
		$content = Json::encode($this->getEntity($response)->getData());
		$response->getBody()->write($content);

		// Setup content type
		return $response
			->withHeader('Content-Type', 'application/json');
	}

	protected function transformResponse(ApiRequest $request, ApiResponse $response): ApiResponse
	{
		// Unify response
		$response = $this->unifyResponse($request, $response);

		// Convert data to array to json
		$content = Json::encode($this->getEntity($response)->getData());
		$response->getBody()->write($content);

		// Setup content type
		return $response
			->withHeader('Content-Type', 'application/json');
	}

	protected function unifyResponse(ApiRequest $request, ApiResponse $response): ApiResponse
	{
		return $this->processSuccess($request, $response);
	}

	protected function unifyException(Throwable $exception, ApiRequest $request, ApiResponse $response): ApiResponse
	{
		if ($exception instanceof ClientErrorException) {
			return $this->processClientError($exception, $request, $response);
		}

		if ($exception instanceof ServerErrorException) {
			return $this->processServerError($exception, $request, $response);
		}

		return $this->processException($exception, $request, $response);
	}

	protected function processSuccess(ApiRequest $request, ApiResponse $response): ApiResponse
	{
		// Setup status code only if it's not set already
		if (!$response->getStatusCode()) {
			$response = $response->withStatus(200);
		}

		return $response
			->withAttribute(ResponseAttributes::ATTR_ENTITY, ArrayEntity::from([
				'status' => self::STATUS_SUCCESS,
				'data' => $this->getEntity($response)->getData(),
			]));
	}

	protected function processClientError(ClientErrorException $exception, ApiRequest $request, ApiResponse $response): ApiResponse
	{
		$data = [
			'code' => $exception->getCode(),
			'error' => $exception->getMessage(),
			'context' => $exception->getContext(),
		];

		return $response
			->withStatus($exception->getCode())
			->withAttribute(ResponseAttributes::ATTR_ENTITY, ArrayEntity::from([
				'status' => self::STATUS_ERROR,
				'data' => $data,
			]));
	}

	protected function processServerError(ServerErrorException $exception, ApiRequest $request, ApiResponse $response): ApiResponse
	{
		return $response
			->withStatus($exception->getCode())
			->withAttribute(ResponseAttributes::ATTR_ENTITY, ArrayEntity::from([
				'status' => self::STATUS_ERROR,
				'message' => $exception->getMessage(),
			]));
	}

	protected function processException(Throwable $exception, ApiRequest $request, ApiResponse $response): ApiResponse
	{
		// Analyze status code
		$code = $exception->getCode();
		$code = $code < 400 || $code > 600 ? 500 : $code;

		return $response
			->withStatus($code)
			->withAttribute(ResponseAttributes::ATTR_ENTITY, ArrayEntity::from([
				'status' => self::STATUS_ERROR,
				'message' => $this->debug ? $exception->getMessage() : 'Application encountered an internal error. Please try again later.',
			]));
	}

}
