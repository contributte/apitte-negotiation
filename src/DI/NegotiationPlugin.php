<?php

namespace Apitte\Negotiation\DI;

use Apitte\Core\DI\ApiExtension;
use Apitte\Core\DI\Plugin\AbstractPlugin;
use Apitte\Core\DI\Plugin\PluginCompiler;
use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Negotiation\ContentNegotiationMiddleware;
use Apitte\Negotiation\ContentUnificationMiddleware;
use Apitte\Negotiation\SuffixNegotiator;
use Apitte\Negotiation\Transformer\JsonTransformer;
use Contributte\Middlewares\DI\MiddlewaresExtension;

class NegotiationPlugin extends AbstractPlugin
{

	const PLUGIN_NAME = 'negotiation';

	/** @var array */
	protected $defaults = [
		'unification' => FALSE,
	];

	/**
	 * @param PluginCompiler $compiler
	 */
	public function __construct(PluginCompiler $compiler)
	{
		parent::__construct($compiler);
		$this->name = self::PLUGIN_NAME;
	}

	/**
	 * Register services
	 *
	 * @return void
	 */
	public function loadPluginConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		$builder->addDefinition($this->prefix('transformer.fallback'))
			->setFactory(JsonTransformer::class)
			->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => '*'])
			->setAutowired(FALSE);

		$builder->addDefinition($this->prefix('transformer.json'))
			->setFactory(JsonTransformer::class)
			->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => 'json'])
			->setAutowired(FALSE);

		$builder->addDefinition($this->prefix('negotiation'))
			->setFactory(ContentNegotiationMiddleware::class)
			->addTag(MiddlewaresExtension::MIDDLEWARE_TAG, ['priority' => 450]);

		$builder->addDefinition($this->prefix('negotiator.suffix'))
			->setFactory(SuffixNegotiator::class)
			->addTag(ApiExtension::NEGOTIATION_NEGOTIATOR_TAG, ['priority' => 100]);

		if ($config['unification'] === TRUE) {
			$builder->addDefinition($this->prefix('unification'))
				->setFactory(ContentUnificationMiddleware::class)
				->addTag(MiddlewaresExtension::MIDDLEWARE_TAG, ['priority' => 460]);
		}
	}

	/**
	 * Decorate services
	 *
	 * @return void
	 */
	public function beforePluginCompile()
	{
		$this->compileTaggedNegotiators();
		$this->compileTaggedTransformers();
	}

	/**
	 * @return void
	 */
	protected function compileTaggedNegotiators()
	{
		$builder = $this->getContainerBuilder();

		// Find all definitions by tag
		$definitions = $builder->findByTag(ApiExtension::NEGOTIATION_NEGOTIATOR_TAG);

		// Ensure we have at least 1 service
		if (!$definitions) {
			throw new InvalidStateException(sprintf('No services with tag "%s"', ApiExtension::NEGOTIATION_NEGOTIATOR_TAG));
		}

		// Sort by priority
		uasort($definitions, function ($a, $b) {
			$p1 = isset($a['priority']) ? $a['priority'] : 10;
			$p2 = isset($b['priority']) ? $b['priority'] : 10;

			if ($p1 == $p2) {
				return 0;
			}

			return ($p1 < $p2) ? -1 : 1;
		});

		// Obtain negotiation
		$negotiation = $builder->getDefinition($this->prefix('negotiation'));

		// Find all services by names
		$negotiators = array_map(function ($name) use ($builder) {
			return $builder->getDefinition($name);
		}, array_keys($definitions));

		// Set services as argument
		$negotiation->getFactory()->arguments = [$negotiators];
	}

	/**
	 * @return void
	 */
	protected function compileTaggedTransformers()
	{
		$builder = $this->getContainerBuilder();

		// Find all definitions by tag
		$definitions = $builder->findByTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG);

		// Ensure we have at least 1 service
		if (!$definitions) {
			throw new InvalidStateException(sprintf('No services with tag "%s"', ApiExtension::NEGOTIATION_TRANSFORMER_TAG));
		}

		// Find all services by names
		$suffixTransformers = [];
		foreach ($definitions as $name => $tag) {
			// Skip invalid transformers
			if (!isset($tag['suffix'])) continue;

			// Find suffix transformer service
			$suffixTransformers[$tag['suffix']] = $builder->getDefinition($name);
		}

		// Suffix ==========================================

		// Obtain suffix negotiator
		$negotiator = $builder->getDefinition($this->prefix('negotiator.suffix'));

		// Set services as argument
		$negotiator->getFactory()->arguments = [$suffixTransformers];
	}

}
