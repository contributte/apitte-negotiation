<?php

namespace Apitte\Negotiation\DI;

use Apitte\Core\Decorator\IDecorator;
use Apitte\Core\DI\ApiExtension;
use Apitte\Core\DI\Helpers;
use Apitte\Core\DI\Plugin\AbstractPlugin;
use Apitte\Core\DI\Plugin\PluginCompiler;
use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Negotiation\ContentNegotiation;
use Apitte\Negotiation\Decorator\ResponseEntityDecorator;
use Apitte\Negotiation\Decorator\ThrowExceptionDecorator;
use Apitte\Negotiation\DefaultNegotiator;
use Apitte\Negotiation\FallbackNegotiator;
use Apitte\Negotiation\Resolver\ArrayEntityResolver;
use Apitte\Negotiation\SuffixNegotiator;
use Apitte\Negotiation\Transformer\CsvTransformer;
use Apitte\Negotiation\Transformer\JsonTransformer;
use Apitte\Negotiation\Transformer\JsonUnifyTransformer;
use Apitte\Negotiation\Transformer\RendererTransformer;

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

		$builder->addDefinition($this->prefix('transformer.json'))
			->setFactory(JsonTransformer::class)
			->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => 'json'])
			->setAutowired(FALSE);

		$builder->addDefinition($this->prefix('transformer.csv'))
			->setFactory(CsvTransformer::class)
			->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => 'csv'])
			->setAutowired(FALSE);

		$builder->addDefinition($this->prefix('transformer.fallback'))
			->setFactory(JsonTransformer::class)
			->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => '*', 'fallback' => '*'])
			->setAutowired(FALSE);

		$builder->addDefinition($this->prefix('transformer.renderer'))
			->setFactory(RendererTransformer::class)
			->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => '#'])
			->setAutowired(FALSE);

		$builder->addDefinition($this->prefix('negotiation'))
			->setFactory(ContentNegotiation::class);

		$builder->addDefinition($this->prefix('negotiator.suffix'))
			->setFactory(SuffixNegotiator::class)
			->addTag(ApiExtension::NEGOTIATION_NEGOTIATOR_TAG, ['priority' => 100]);

		$builder->addDefinition($this->prefix('negotiator.default'))
			->setFactory(DefaultNegotiator::class)
			->addTag(ApiExtension::NEGOTIATION_NEGOTIATOR_TAG, ['priority' => 200]);

		$builder->addDefinition($this->prefix('negotiator.fallback'))
			->setFactory(FallbackNegotiator::class)
			->addTag(ApiExtension::NEGOTIATION_NEGOTIATOR_TAG, ['priority' => 300]);

		$builder->addDefinition($this->prefix('decorator.response'))
			->setFactory(ResponseEntityDecorator::class)
			->addTag(ApiExtension::CORE_DECORATOR_TAG, ['priority' => 500, 'type' => [IDecorator::DISPATCHER_AFTER, IDecorator::DISPATCHER_EXCEPTION]]);

		if ($config['unification'] === TRUE) {
			$builder->removeDefinition($this->prefix('transformer.fallback'));
			$builder->removeDefinition($this->prefix('transformer.json'));

			$builder->addDefinition($this->prefix('transformer.fallback'))
				->setFactory(JsonUnifyTransformer::class)
				->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => '*', 'fallback' => '*'])
				->setAutowired(FALSE);
			$builder->addDefinition($this->prefix('transformer.json'))
				->setFactory(JsonUnifyTransformer::class)
				->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => 'json'])
				->setAutowired(FALSE);
		}

		if ($config['catchException'] === FALSE && $globalConfig['debug'] === TRUE) {
			$builder->addDefinition($this->prefix('decorator.throwException'))
				->setFactory(ThrowExceptionDecorator::class)
				->addTag(ApiExtension::CORE_DECORATOR_TAG, ['priority' => 99, 'type' => IDecorator::DISPATCHER_EXCEPTION]);
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

		// Init temporary array for services
		$transformers = [
			'suffix' => [],
			'fallback' => NULL,
		];

		// Find all services by names
		foreach ($definitions as $name => $tag) {
			if (isset($tag['suffix'])) {
				// Find suffix transformer service
				$transformers['suffix'][$tag['suffix']] = $builder->getDefinition($name);
			}

			if (isset($tag['fallback'])) {
				$transformers['fallback'] = $builder->getDefinition($name);
			}
		}

		// Obtain suffix negotiator
		$suffixNegotiator = $builder->getDefinition($this->prefix('negotiator.suffix'));
		$suffixNegotiator->setArguments([$transformers['suffix']]);

		// Obtain default negotiator
		$defaultNegotiator = $builder->getDefinition($this->prefix('negotiator.default'));
		$defaultNegotiator->setArguments([$transformers['suffix']]);

		// Obtain fallback negotiator
		$fallbackNegotiator = $builder->getDefinition($this->prefix('negotiator.fallback'));
		$fallbackNegotiator->setArguments([$transformers['fallback']]);
	}

}
