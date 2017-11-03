<?php

namespace Apitte\Negotiation\Transformer;

use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\Core\Http\ResponseAttributes;
use Apitte\Negotiation\Http\ArrayEntity;
use Exception;
use Nette\Utils\Json;

class JsonUnifyTransformer extends AbstractTransformer
{

	const DEFAULT_SUCCESS_CODE = 'success';
	const DEFAULT_CLIENT_ERROR_CODE = 'error.client';
	const DEFAULT_SERVER_ERROR_CODE = 'error.server';
	const DEFAULT_EXCEPTION_CODE = 'error';

	/** @var array */
	protected $options = [
		'codes' => [
			self::DEFAULT_SUCCESS_CODE => 200,
			self::DEFAULT_CLIENT_ERROR_CODE => 400,
			self::DEFAULT_SERVER_ERROR_CODE => 500,
			self::DEFAULT_EXCEPTION_CODE => 555,
		],
	];

	// Statuses
	const STATUS_SUCCESS = 'success';
	const STATUS_ERROR = 'error';

	/**
	 * @param array $options
	 */
	public function __construct(array $options = [])
	{
		$this->options = array_merge($this->options, $options);
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function setOption($key, $value)
	{
		$this->options[$key] = $value;
	}

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
			return $this->transformException($context['exception'], $request, $response);
		}

		return $this->transformResponse($request, $response);
	}

	/**
	 * @param Exception $exception
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @return ApiResponse
	 */
	protected function transformException(Exception $exception, ApiRequest $request, ApiResponse $response)
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

	/**
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @return ApiResponse
	 */
	protected function transformResponse(ApiRequest $request, ApiResponse $response)
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

	/**
	 * UNIFICATION *************************************************************
	 */

	/**
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @return ApiResponse
	 */
	protected function unifyResponse(ApiRequest $request, ApiResponse $response)
	{
		return $this->processSuccess($request, $response);
	}

	/**
	 * @param Exception $exception
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @return ApiResponse
	 */
	protected function unifyException(Exception $exception, ApiRequest $request, ApiResponse $response)
	{
		if ($exception instanceof ClientErrorException) {
			return $this->processClientError($exception, $request, $response);
		}

		if ($exception instanceof ServerErrorException) {
			return $this->processServerError($exception, $request, $response);
		}

		return $this->processException($exception, $request, $response);
	}

	/**
	 * PROCESSING **************************************************************
	 */

	/**
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @return ApiResponse
	 */
	protected function processSuccess(ApiRequest $request, ApiResponse $response)
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

	/**
	 * @param ClientErrorException $exception
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @return ApiResponse
	 */
	protected function processClientError(ClientErrorException $exception, ApiRequest $request, ApiResponse $response)
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

	/**
	 * @param ServerErrorException $exception
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @return ApiResponse
	 */
	protected function processServerError(ServerErrorException $exception, ApiRequest $request, ApiResponse $response)
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

	/**
	 * @param Exception $exception
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @return ApiResponse
	 */
	protected function processException(Exception $exception, ApiRequest $request, ApiResponse $response)
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
