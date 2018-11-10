<?php declare(strict_types = 1);

namespace Apitte\Negotiation\DI;

use Apitte\Core\Decorator\IDecorator;
use Apitte\Core\DI\ApiExtension;
use Apitte\Core\DI\Helpers;
use Apitte\Core\DI\Plugin\AbstractPlugin;
use Apitte\Core\DI\Plugin\CoreDecoratorPlugin;
use Apitte\Core\DI\Plugin\PluginCompiler;
use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Negotiation\ContentNegotiation;
use Apitte\Negotiation\Decorator\ResponseEntityDecorator;
use Apitte\Negotiation\Decorator\ThrowExceptionDecorator;
use Apitte\Negotiation\DefaultNegotiator;
use Apitte\Negotiation\FallbackNegotiator;
use Apitte\Negotiation\SuffixNegotiator;
use Apitte\Negotiation\Transformer\CsvTransformer;
use Apitte\Negotiation\Transformer\JsonTransformer;
use Apitte\Negotiation\Transformer\JsonUnifyTransformer;
use Apitte\Negotiation\Transformer\RendererTransformer;

class NegotiationPlugin extends AbstractPlugin
{

	public const PLUGIN_NAME = 'negotiation';

	/** @var mixed[] */
	protected $defaults = [
		'unification' => false,
		'catchException' => false,
	];

	public function __construct(PluginCompiler $compiler)
	{
		parent::__construct($compiler);
		$this->name = self::PLUGIN_NAME;
	}

	/**
	 * Register services
	 */
	public function loadPluginConfiguration(): void
	{
		if ($this->compiler->getPlugin(CoreDecoratorPlugin::PLUGIN_NAME) === null) {
			throw new InvalidStateException(sprintf('Plugin "%s" must be enabled', CoreDecoratorPlugin::class));
		}

		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();
		$globalConfig = $this->compiler->getExtension()->getConfig();

		$builder->addDefinition($this->prefix('transformer.json'))
			->setFactory(JsonTransformer::class)
			->addSetup('setDebugMode', [$globalConfig['debug']])
			->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => 'json'])
			->setAutowired(false);

		$builder->addDefinition($this->prefix('transformer.csv'))
			->setFactory(CsvTransformer::class)
			->addSetup('setDebugMode', [$globalConfig['debug']])
			->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => 'csv'])
			->setAutowired(false);

		$builder->addDefinition($this->prefix('transformer.fallback'))
			->setFactory(JsonTransformer::class)
			->addSetup('setDebugMode', [$globalConfig['debug']])
			->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => '*', 'fallback' => '*'])
			->setAutowired(false);

		$builder->addDefinition($this->prefix('transformer.renderer'))
			->setFactory(RendererTransformer::class)
			->addSetup('setDebugMode', [$globalConfig['debug']])
			->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => '#'])
			->setAutowired(false);

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
			->addTag(ApiExtension::CORE_DECORATOR_TAG, ['priority' => 500, 'type' => [IDecorator::ON_HANDLER_AFTER, IDecorator::ON_DISPATCHER_EXCEPTION]]);

		if ($config['unification'] === true) {
			$builder->removeDefinition($this->prefix('transformer.fallback'));
			$builder->removeDefinition($this->prefix('transformer.json'));

			$builder->addDefinition($this->prefix('transformer.fallback'))
				->setFactory(JsonUnifyTransformer::class)
				->addSetup('setDebugMode', [$globalConfig['debug']])
				->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => '*', 'fallback' => '*'])
				->setAutowired(false);
			$builder->addDefinition($this->prefix('transformer.json'))
				->setFactory(JsonUnifyTransformer::class)
				->addSetup('setDebugMode', [$globalConfig['debug']])
				->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => 'json'])
				->setAutowired(false);
		}

		if ($config['catchException'] === false && $globalConfig['debug'] === true) {
			$builder->addDefinition($this->prefix('decorator.throwException'))
				->setFactory(ThrowExceptionDecorator::class)
				->addTag(ApiExtension::CORE_DECORATOR_TAG, ['priority' => 99, 'type' => IDecorator::ON_DISPATCHER_EXCEPTION]);
		}
	}

	/**
	 * Decorate services
	 */
	public function beforePluginCompile(): void
	{
		$this->compileTaggedNegotiators();
		$this->compileTaggedTransformers();
	}

	protected function compileTaggedNegotiators(): void
	{
		$builder = $this->getContainerBuilder();

		// Find all definitions by tag
		$definitions = $builder->findByTag(ApiExtension::NEGOTIATION_NEGOTIATOR_TAG);

		// Ensure we have at least 1 service
		if ($definitions === []) {
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

	protected function compileTaggedTransformers(): void
	{
		$builder = $this->getContainerBuilder();

		// Find all definitions by tag
		$definitions = $builder->findByTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG);

		// Ensure we have at least 1 service
		if ($definitions === []) {
			throw new InvalidStateException(sprintf('No services with tag "%s"', ApiExtension::NEGOTIATION_TRANSFORMER_TAG));
		}

		// Init temporary array for services
		$transformers = [
			'suffix' => [],
			'fallback' => null,
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
