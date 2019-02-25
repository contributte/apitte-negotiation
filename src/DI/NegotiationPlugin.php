<?php declare(strict_types = 1);

namespace Apitte\Negotiation\DI;

use Apitte\Core\DI\ApiExtension;
use Apitte\Core\DI\Helpers;
use Apitte\Core\DI\Plugin\AbstractPlugin;
use Apitte\Core\DI\Plugin\CoreDecoratorPlugin;
use Apitte\Core\DI\Plugin\PluginCompiler;
use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Negotiation\ContentNegotiation;
use Apitte\Negotiation\Decorator\ResponseEntityDecorator;
use Apitte\Negotiation\DefaultNegotiator;
use Apitte\Negotiation\FallbackNegotiator;
use Apitte\Negotiation\INegotiator;
use Apitte\Negotiation\SuffixNegotiator;
use Apitte\Negotiation\Transformer\CsvTransformer;
use Apitte\Negotiation\Transformer\ITransformer;
use Apitte\Negotiation\Transformer\JsonTransformer;
use Apitte\Negotiation\Transformer\JsonUnifyTransformer;
use Apitte\Negotiation\Transformer\RendererTransformer;

class NegotiationPlugin extends AbstractPlugin
{

	public const PLUGIN_NAME = 'negotiation';

	/** @var mixed[] */
	protected $defaults = [
		'unification' => false,
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
			->addTag(ApiExtension::CORE_DECORATOR_TAG, ['priority' => 500]);

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
	}

	/**
	 * Decorate services
	 */
	public function beforePluginCompile(): void
	{
		$this->compileNegotiators();
		$this->compileTransformers();
	}

	protected function compileNegotiators(): void
	{
		$builder = $this->getContainerBuilder();

		// Find all definitions by tag
		$definitions = $builder->findByType(INegotiator::class);

		// Sort by priority
		$definitions = Helpers::sortByPriorityInTag(ApiExtension::NEGOTIATION_NEGOTIATOR_TAG, $definitions);

		// Setup negotiators
		$builder->getDefinition($this->prefix('negotiation'))
			->setArguments([$definitions]);
	}

	protected function compileTransformers(): void
	{
		$builder = $this->getContainerBuilder();

		// Find all definitions by tag
		$definitions = $builder->findByType(ITransformer::class);

		// Init temporary array for services
		$transformers = [
			'suffix' => [],
			'fallback' => null,
		];

		foreach ($definitions as $definition) {
			$tag = $definition->getTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG);

			if (isset($tag['suffix'])) {
				// Find suffix transformer service
				$transformers['suffix'][$tag['suffix']] = $definition;
			}

			if (isset($tag['fallback'])) {
				$transformers['fallback'] = $definition;
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
