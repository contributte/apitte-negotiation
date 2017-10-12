<?php

namespace Apitte\Negotiation;

use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Mapping\Http\ApiRequest;
use Apitte\Mapping\Http\ApiResponse;
use Apitte\Negotiation\Http\ArrayEntity;
use Exception;

/**
 * @see https://labs.omniti.com/labs/jsend
 */
class ContentUnification
{

	// Attributes in ApiRequest
	const ATTR_SKIP_UNIFICATION = 'apitte.negotiation.skip.unification';

	// Status codes
	const DEFAULT_SUCCESS_CODE = 200;
	const DEFAULT_CLIENT_ERROR_CODE = 400;
	const DEFAULT_SERVER_ERROR_CODE = 500;
	const DEFAULT_EXCEPTION_CODE = 505;

	// Statuses
	const STATUS_SUCCESS = 'success';
	const STATUS_ERROR = 'error';

	/**
	 * API *********************************************************************
	 */

	/**
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @param Exception|NULL $exception
	 * @return ApiResponse
	 */
	public function unifyResponse(ApiRequest $request, ApiResponse $response, Exception $exception = NULL)
	{
		return $this->processSuccess($request, $response);
	}

	/**
	 * @param Exception $exception
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @return ApiResponse
	 */
	public function unifyException(Exception $exception, ApiRequest $request, ApiResponse $response)
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
			$response = $response->withStatus(self::DEFAULT_SUCCESS_CODE);
		}

		// Skip processing if unified data not provided
		if (!($response->getEntity() instanceof ArrayEntity)) return $response;

		return $response
			->withEntity(ArrayEntity::from([
				'status' => self::STATUS_SUCCESS,
				'data' => $response->getEntity()->toArray(),
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
		$code = $code < 400 || $code > 500 ? self::DEFAULT_CLIENT_ERROR_CODE : $code;

		$data = [
			'code' => $code,
			'error' => $exception->getMessage(),
			'context' => $exception->getContext(),
		];

		return $response
			->withStatus($code)
			->withEntity(ArrayEntity::from([
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
		$code = $code < 500 || $code > 600 ? self::DEFAULT_SERVER_ERROR_CODE : $code;

		return $response
			->withStatus($code)
			->withEntity(ArrayEntity::from([
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
		$code = $code < 400 || $code > 600 ? self::DEFAULT_EXCEPTION_CODE : $code;

		return $response
			->withStatus($code)
			->withEntity(ArrayEntity::from([
				'status' => self::STATUS_ERROR,
				'message' => $exception->getMessage(),
			]));
	}

}
