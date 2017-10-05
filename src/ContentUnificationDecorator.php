<?php

namespace Apitte\Negotiation;

use Apitte\Mapping\Decorator\IExceptionDecorator;
use Apitte\Mapping\Decorator\IResponseDecorator;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ContentUnificationDecorator implements IResponseDecorator, IExceptionDecorator
{

	/** @var ContentUnification */
	private $unification;

	/**
	 * @param ContentUnification $unification
	 */
	public function __construct(ContentUnification $unification)
	{
		$this->unification = $unification;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function decorateResponse(ServerRequestInterface $request, ResponseInterface $response)
	{
		return $this->unification->unifyResponse($request, $response);
	}

	/**
	 * @param Exception $exception
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function decorateException(Exception $exception, ServerRequestInterface $request, ResponseInterface $response)
	{
		return $this->unification->unifyException($exception, $request, $response);
	}

}
