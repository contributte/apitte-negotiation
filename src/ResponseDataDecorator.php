<?php

namespace Apitte\Negotiation;

use Apitte\Mapping\Decorator\IExceptionDecorator;
use Apitte\Mapping\Decorator\IResponseDecorator;
use Apitte\Mapping\Http\ApiRequest;
use Apitte\Mapping\Http\ApiResponse;
use Apitte\Negotiation\Resolver\IResolver;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResponseDataDecorator implements IResponseDecorator, IExceptionDecorator
{

	// Resolvers
	const FALLBACK = '*';

	/** @var IResolver[] */
	private $resolvers = [];

	/**
	 * @param string $class
	 * @param IResolver $resolver
	 * @return void
	 */
	public function addResolver($class, IResolver $resolver)
	{
		$this->resolvers[$class] = $resolver;
	}

	/**
	 * @param ApiRequest|ServerRequestInterface $request
	 * @param ApiResponse|ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function decorateResponse(ServerRequestInterface $request, ResponseInterface $response)
	{
		$resolver = $this->getResolver($response);

		if ($resolver) {
			return $resolver->resolveResponse($request, $response);
		}

		if (isset($this->resolvers[self::FALLBACK])) {
			return $this->resolvers[self::FALLBACK]->resolveResponse($request, $response);
		}

		return $response;
	}

	/**
	 * @param Exception $exception
	 * @param ApiRequest|ServerRequestInterface $request
	 * @param ApiResponse|ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function decorateException(Exception $exception, ServerRequestInterface $request, ResponseInterface $response)
	{
		$resolver = $this->getResolver($response);

		if ($resolver) {
			return $resolver->resolveException($exception, $request, $response);
		}

		if (isset($this->resolvers[self::FALLBACK])) {
			return $this->resolvers[self::FALLBACK]->resolveException($exception, $request, $response);
		}

		return $response;
	}

	/**
	 * @param ApiResponse $response
	 * @return IResolver|NULL
	 */
	protected function getResolver(ApiResponse $response)
	{
		$entityClass = get_class($response->getEntity());

		foreach ($this->resolvers as $resolverClass => $resolver) {
			// Skip fallback
			if ($resolverClass === self::FALLBACK) continue;

			// Find resolver
			if ($resolverClass === $entityClass || is_subclass_of($resolverClass, $entityClass)) {
				return $resolver;
			}
		}

		return NULL;
	}

}
