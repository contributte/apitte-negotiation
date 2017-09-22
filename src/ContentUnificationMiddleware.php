<?php

namespace Apitte\Negotiation;

use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Negotiation\Http\ArrayStream;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @see https://labs.omniti.com/labs/jsend
 */
class ContentUnificationMiddleware
{

	// Attributes in ServerRequestInterface
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
	 * API - MIDDLEWARE ********************************************************
	 */

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @param callable $next
	 * @return ResponseInterface
	 */
	public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
	{
		try {
			// Pass to next middleware
			$response = $next($request, $response);

			return $this->processSuccess($request, $response);
		} catch (ClientErrorException $e) {
			return $this->processClientError($request, $response, $e);
		} catch (ServerErrorException $e) {
			return $this->processServerError($request, $response, $e);
		} catch (Exception $e) {
			return $this->processException($request, $response, $e);
		}
	}

	/**
	 * PROCESSING **************************************************************
	 */

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	protected function processSuccess(ServerRequestInterface $request, ResponseInterface $response)
	{
		if (!$response->getStatusCode()) {
			$response = $response->withStatus(self::DEFAULT_SUCCESS_CODE);
		}

		// Skip processing if unified data not provided
		if (!$response->getBody() instanceof ArrayStream) return $response;

		/** @var ArrayStream $body */
		$body = $response->getBody();

		// Setup status code only if it's not set already
		return $response->withBody(ArrayStream::from($response)->with([
			'status' => self::STATUS_SUCCESS,
			'data' => $body->getData(),
		]));
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @param ClientErrorException $exception
	 * @return ResponseInterface
	 */
	protected function processClientError(ServerRequestInterface $request, ResponseInterface $response, ClientErrorException $exception)
	{
		// Analyze status code
		$code = $exception->getCode();
		$code = $code < 400 || $code > 500 ? self::DEFAULT_CLIENT_ERROR_CODE : $code;

		return $response
			->withStatus($code)
			->withBody(ArrayStream::from($response)->with([
				'status' => self::STATUS_ERROR,
				'data' => $exception->getContext(),
			]));
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @param ServerErrorException $exception
	 * @return ResponseInterface
	 */
	protected function processServerError(ServerRequestInterface $request, ResponseInterface $response, ServerErrorException $exception)
	{
		// Analyze status code
		$code = $exception->getCode();
		$code = $code < 500 || $code > 600 ? self::DEFAULT_SERVER_ERROR_CODE : $code;

		return $response
			->withStatus($code)
			->withBody(ArrayStream::from($response)->with([
				'status' => self::STATUS_ERROR,
				'message' => $exception->getMessage(),
			]));
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @param Exception $exception
	 * @return ResponseInterface
	 */
	protected function processException(ServerRequestInterface $request, ResponseInterface $response, Exception $exception)
	{
		// Analyze status code
		$code = $exception->getCode();
		$code = $code < 400 || $code > 600 ? self::DEFAULT_EXCEPTION_CODE : $code;

		return $response
			->withStatus($code)
			->withBody(ArrayStream::from($response)->with([
				'status' => self::STATUS_ERROR,
				'message' => $exception->getMessage(),
			]));
	}

}
