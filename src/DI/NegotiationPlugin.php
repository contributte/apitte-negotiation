<?php

namespace Apitte\Negotiation\DI;

use Apitte\Core\DI\ApiExtension;
use Apitte\Core\DI\Helpers;
use Apitte\Core\DI\Plugin\AbstractPlugin;
use Apitte\Core\DI\Plugin\PluginCompiler;
use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Negotiation\ContentNegotiation;
use Apitte\Negotiation\Http\ArrayEntity;
use Apitte\Negotiation\Resolver\ArrayEntityResolver;
use Apitte\Negotiation\ResponseDataDecorator;
use Apitte\Negotiation\SuffixNegotiator;
use Apitte\Negotiation\ThrowExceptionDecorator;
use Apitte\Negotiation\Transformer\CsvTransformer;
use Apitte\Negotiation\Transformer\JsonTransformer;
use Apitte\Negotiation\Transformer\JsonUnifyTransformer;

class NegotiationPlugin extends AbstractPlugin
{

	const PLUGIN_NAME = 'negotiation';

	/** @var array */
	protected $defaults = [
		'unification' => FALSE,
		'catchException' => FALSE,
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
		$globalConfig = $this->compiler->getExtension()->getConfig();

		$builder->addDefinition($this->prefix('transformer.fallback'))
			->setFactory(JsonTransformer::class)
			->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => '*'])
			->setAutowired(FALSE);

		$builder->addDefinition($this->prefix('transformer.json'))
			->setFactory(JsonTransformer::class)
			->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => 'json'])
			->setAutowired(FALSE);

		$builder->addDefinition($this->prefix('transformer.csv'))
			->setFactory(CsvTransformer::class)
			->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => 'csv'])
			->setAutowired(FALSE);

		$builder->addDefinition($this->prefix('negotiation'))
			->setFactory(ContentNegotiation::class);

		$builder->addDefinition($this->prefix('negotiator.suffix'))
			->setFactory(SuffixNegotiator::class)
			->addTag(ApiExtension::NEGOTIATION_NEGOTIATOR_TAG, ['priority' => 100]);

		$builder->addDefinition($this->prefix('decorator.responsedata'))
			->setFactory(ResponseDataDecorator::class)
			->addTag(ApiExtension::MAPPING_DECORATOR_TAG, ['priority' => 500]);

		$builder->addDefinition($this->prefix('resolver.arrayentity'))
			->setFactory(ArrayEntityResolver::class)
			->addTag(ApiExtension::NEGOTIATION_RESOLVER_TAG, ['entity' => ArrayEntity::class]);

		$builder->addDefinition($this->prefix('resolver.fallback'))
			->setFactory(ArrayEntityResolver::class)
			->addTag(ApiExtension::NEGOTIATION_RESOLVER_TAG, ['entity' => ResponseDataDecorator::FALLBACK]);

		if ($config['unification'] === TRUE) {
			$builder->removeDefinition($this->prefix('transformer.fallback'));
			$builder->removeDefinition($this->prefix('transformer.json'));

			$builder->addDefinition($this->prefix('transformer.fallback'))
				->setFactory(JsonUnifyTransformer::class)
				->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => '*'])
				->setAutowired(FALSE);
			$builder->addDefinition($this->prefix('transformer.json'))
				->setFactory(JsonUnifyTransformer::class)
				->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => 'json'])
				->setAutowired(FALSE);
		}

		if ($config['catchException'] === FALSE && $globalConfig['debug'] === TRUE) {
			$builder->addDefinition($this->prefix('decorator.throwexception'))
				->setFactory(ThrowExceptionDecorator::class)
				->addTag(ApiExtension::MAPPING_DECORATOR_TAG, ['priority' => 99]);
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
		$this->compileTaggedResolvers();
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
		$definitions = Helpers::sort($definitions);

		// Find all services by names
		$negotiators = Helpers::getDefinitions($definitions, $builder);

		// Obtain negotiation
		$negotiation = $builder->getDefinition($this->prefix('negotiation'));

		// Set services as argument
		$negotiation->setArguments([$negotiators]);
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
		$negotiator->setArguments([$suffixTransformers]);
	}

	/**
	 * @return void
	 */
	protected function compileTaggedResolvers()
	{
		$builder = $this->getContainerBuilder();

		// Find all definitions by tag
		$definitions = $builder->findByTag(ApiExtension::NEGOTIATION_RESOLVER_TAG);

		// Ensure we have at least 1 service
		if (!$definitions) return;

		// Obtain response data decorator
		$decorator = $builder->getDefinition($this->prefix('decorator.responsedata'));

		// Find all services by names
		foreach ($definitions as $name => $tag) {
			// Skip invalid resolvers
			if (!isset($tag['entity'])) continue;

			// Add resolver to decorator
			$decorator->addSetup('addResolver', [$tag['entity'], $builder->getDefinition($name)]);
		}
	}

}
