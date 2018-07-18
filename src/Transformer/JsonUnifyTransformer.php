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

	public const
		DEFAULT_SUCCESS_CODE = 'success',
		DEFAULT_CLIENT_ERROR_CODE = 'error.client',
		DEFAULT_SERVER_ERROR_CODE = 'error.server',
		DEFAULT_EXCEPTION_CODE = 'error';

	/** @var mixed[] */
	protected $options = [
		'codes' => [
			self::DEFAULT_SUCCESS_CODE => 200,
			self::DEFAULT_CLIENT_ERROR_CODE => 400,
			self::DEFAULT_SERVER_ERROR_CODE => 500,
			self::DEFAULT_EXCEPTION_CODE => 555,
		],
	];

	// Statuses
	public const
		STATUS_SUCCESS = 'success',
		STATUS_ERROR = 'error';

	/**
	 * @param mixed[] $options
	 */
	public function __construct(array $options = [])
	{
		$this->options = array_merge($this->options, $options);
	}

	/**
	 * @param mixed $value
	 */
	public function setOption(string $key, $value): void
	{
		$this->options[$key] = $value;
	}

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
		$response = $response
			->withHeader('Content-Type', 'application/json');

		return $response;
	}

	protected function transformResponse(ApiRequest $request, ApiResponse $response): ApiResponse
	{
		// Unify response
		$response = $this->unifyResponse($request, $response);

		// Convert data to array to json
		$content = Json::encode($this->getEntity($response)->getData());
		$response->getBody()->write($content);

		// Setup content type
		$response = $response
			->withHeader('Content-Type', 'application/json');

		return $response;
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
			$response = $response->withStatus($this->options['codes'][self::DEFAULT_SUCCESS_CODE]);
		}

		return $response
			->withAttribute(ResponseAttributes::ATTR_ENTITY, ArrayEntity::from([
				'status' => self::STATUS_SUCCESS,
				'data' => $this->getEntity($response)->getData(),
			]));
	}

	protected function processClientError(ClientErrorException $exception, ApiRequest $request, ApiResponse $response): ApiResponse
	{
		// Analyze status code
		$code = $exception->getCode();
		$code = $code < 400 || $code > 500 ? $this->options['codes'][self::DEFAULT_CLIENT_ERROR_CODE] : $code;

		$data = [
			'code' => $code,
			'error' => $exception->getMessage(),
			'context' => $exception->getContext(),
		];

		return $response
			->withStatus($code)
			->withAttribute(ResponseAttributes::ATTR_ENTITY, ArrayEntity::from([
				'status' => self::STATUS_ERROR,
				'data' => $data,
			]));
	}

	protected function processServerError(ServerErrorException $exception, ApiRequest $request, ApiResponse $response): ApiResponse
	{
		// Analyze status code
		$code = $exception->getCode();
		$code = $code < 500 || $code > 600 ? $this->options['codes'][self::DEFAULT_SERVER_ERROR_CODE] : $code;

		return $response
			->withStatus($code)
			->withAttribute(ResponseAttributes::ATTR_ENTITY, ArrayEntity::from([
				'status' => self::STATUS_ERROR,
				'message' => $exception->getMessage(),
			]));
	}

	protected function processException(Throwable $exception, ApiRequest $request, ApiResponse $response): ApiResponse
	{
		// Analyze status code
		$code = $exception->getCode();
		$code = $code < 400 || $code > 600 ? $this->options['codes'][self::DEFAULT_EXCEPTION_CODE] : $code;

		return $response
			->withStatus($code)
			->withAttribute(ResponseAttributes::ATTR_ENTITY, ArrayEntity::from([
				'status' => self::STATUS_ERROR,
				'message' => $exception->getMessage(),
			]));
	}

}
