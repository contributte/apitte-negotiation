<?php

namespace Apitte\Negotiation\Decorator;

use Apitte\Core\Decorator\IDecorator;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\Negotiation\Resolver\IResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResponseEntityDecorator implements IDecorator
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
	 * @param array $context
	 * @return ServerRequestInterface|ResponseInterface
	 */
	public function decorate(ServerRequestInterface $request, ResponseInterface $response, array $context = [])
	{
		$resolver = $this->getResolver($response);

		if ($resolver) {
			return $resolver->resolve($request, $response, $context);
		}

		if (isset($this->resolvers[self::FALLBACK])) {
			return $this->resolvers[self::FALLBACK]->resolve($request, $response, $context);
		}

		return $response;
	}

	/**
	 * @param ApiResponse $response
	 * @return IResolver|NULL
	 */
	protected function getResolver(ApiResponse $response)
	{
		// Early return if entity is not provided
		if (!($entity = $response->getEntity())) return NULL;

		// Get class name of entity
		$entityClass = get_class($entity);

		foreach ($this->resolvers as $resolverClass => $resolver) {
			// Skip fallback
			if ($resolverClass === self::FALLBACK) continue;

			// Find resolver
			if ($resolverClass === $entityClass || is_subclass_of($entity, $resolverClass)) {
				return $resolver;
			}
		}

		return NULL;
	}

}
