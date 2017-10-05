<?php

namespace Apitte\Negotiation;

use Apitte\Mapping\Decorator\IExceptionDecorator;
use Apitte\Mapping\Decorator\IRequestDecorator;
use Apitte\Mapping\Decorator\IResponseDecorator;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ContentNegotiationDecorator implements IRequestDecorator, IResponseDecorator, IExceptionDecorator
{

	/** @var ContentNegotiation */
	private $contentNegotiation;

	/**
	 * @param ContentNegotiation $cn
	 */
	public function __construct(ContentNegotiation $cn)
	{
		$this->contentNegotiation = $cn;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface|ServerRequestInterface
	 */
	public function decorateRequest(ServerRequestInterface $request, ResponseInterface $response)
	{
		return $this->contentNegotiation->negotiateRequest($request, $response);
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function decorateResponse(ServerRequestInterface $request, ResponseInterface $response)
	{
		return $this->contentNegotiation->negotiateResponse($request, $response);
	}

	/**
	 * @param Exception $exception
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function decorateException(Exception $exception, ServerRequestInterface $request, ResponseInterface $response)
	{
		return $this->contentNegotiation->negotiateException($exception, $request, $response);
	}
}
