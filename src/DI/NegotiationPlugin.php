<?php

namespace Apitte\Negotiation\DI;

use Apitte\Core\DI\ApiExtension;
use Apitte\Core\DI\Helpers;
use Apitte\Core\DI\Plugin\AbstractPlugin;
use Apitte\Core\DI\Plugin\PluginCompiler;
use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Negotiation\ContentNegotiation;
use Apitte\Negotiation\ContentNegotiationDecorator;
use Apitte\Negotiation\ContentUnification;
use Apitte\Negotiation\ContentUnificationDecorator;
use Apitte\Negotiation\SuffixNegotiator;
use Apitte\Negotiation\Transformer\CsvTransformer;
use Apitte\Negotiation\Transformer\JsonTransformer;

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

		$builder->addDefinition($this->prefix('transformer.csv'))
			->setFactory(CsvTransformer::class)
			->addTag(ApiExtension::NEGOTIATION_TRANSFORMER_TAG, ['suffix' => 'csv'])
			->setAutowired(FALSE);

		$builder->addDefinition($this->prefix('negotiation'))
			->setFactory(ContentNegotiation::class);

		$builder->addDefinition($this->prefix('negotiation.decorator'))
			->setFactory(ContentNegotiationDecorator::class)
			->addTag(ApiExtension::MAPPING_DECORATOR_TAG, ['priority' => 100]);

		$builder->addDefinition($this->prefix('negotiator.suffix'))
			->setFactory(SuffixNegotiator::class)
			->addTag(ApiExtension::NEGOTIATION_NEGOTIATOR_TAG, ['priority' => 100]);

		if ($config['unification'] === TRUE) {
			$builder->addDefinition($this->prefix('unification'))
				->setFactory(ContentUnification::class);

			$builder->addDefinition($this->prefix('unification.decorator'))
				->setFactory(ContentUnificationDecorator::class)
				->addTag(ApiExtension::MAPPING_DECORATOR_TAG, ['priority' => 500]);
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

}
