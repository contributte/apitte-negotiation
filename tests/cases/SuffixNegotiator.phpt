<?php

/**
 * Test: SuffixNegotiator
 */

require_once __DIR__ . '/../bootstrap.php';

use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\Core\Schema\Endpoint;
use Apitte\Core\Schema\EndpointNegotiation;
use Apitte\Negotiation\Http\ArrayEntity;
use Apitte\Negotiation\SuffixNegotiator;
use Apitte\Negotiation\Transformer\JsonTransformer;
use Contributte\Psr7\Psr7ResponseFactory;
use Contributte\Psr7\Psr7ServerRequestFactory;
use Tester\Assert;

// No transformer
test(function () {
	Assert::exception(function () {
		$negotiation = new SuffixNegotiator([]);
		$negotiation->negotiate(
			new ApiRequest(Psr7ServerRequestFactory::fromSuperGlobal()),
			new ApiResponse(Psr7ResponseFactory::fromGlobal())
		);
	}, InvalidStateException::class, 'Please add at least one transformer');
});

// Null response (no suitable transformer)
test(function () {
	$negotiation = new SuffixNegotiator(['.json' => new JsonTransformer()]);

	$request = new ApiRequest(Psr7ServerRequestFactory::fromSuperGlobal()->withNewUri('https://contributte.org'));
	$response = new ApiResponse(Psr7ResponseFactory::fromGlobal());

	Assert::null($negotiation->negotiate($request, $response));
});

// JSON negotiation (according to .json suffix in URL)
test(function () {
	$negotiation = new SuffixNegotiator(['json' => new JsonTransformer()]);

	$enpoint = new Endpoint();
	$enpoint->addNegotiation($en = new EndpointNegotiation());
	$en->setSuffix('.json');

	$request = new ApiRequest(Psr7ServerRequestFactory::fromSuperGlobal()->withNewUri('https://contributte.org/foo.json'));
	$response = new ApiResponse(Psr7ResponseFactory::fromGlobal());
	$response = $response->withEntity(ArrayEntity::from(['foo' => 'bar']))
		->withEndpoint($enpoint);

	// 2# Negotiate response (PSR7 body contains encoded json data)
	$res = $negotiation->negotiate($request, $response);
	Assert::equal('{"foo":"bar"}', (string) $res->getBody());
});
