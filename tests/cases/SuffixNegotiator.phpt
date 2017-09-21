<?php

/**
 * Test: SuffixNegotiator
 */

require_once __DIR__ . '/../bootstrap.php';

use Apitte\Core\Exception\Logical\InvalidStateException;
use Apitte\Negotiation\SuffixNegotiator;
use Apitte\Negotiation\Transformer\JsonTransformer;
use Contributte\Psr7\Psr7ResponseFactory;
use Contributte\Psr7\Psr7ServerRequestFactory;
use Tester\Assert;

// No transformer
test(function () {
	Assert::exception(function () {
		$negotiation = new SuffixNegotiator([]);
		$negotiation->negotiateResponse(Psr7ServerRequestFactory::fromSuperGlobal(), Psr7ResponseFactory::fromGlobal());
	}, InvalidStateException::class, 'Please add at least one transformer');
});

// Same response (no suitable transformer)
test(function () {
	$negotiation = new SuffixNegotiator(['.json' => new JsonTransformer()]);

	$request = Psr7ServerRequestFactory::fromSuperGlobal()->withNewUri('https://contributte.org');
	$response = Psr7ResponseFactory::fromGlobal();

	// 1# Negotiate request (same object as given);
	Assert::same($request, $negotiation->negotiateRequest($request, $response));

	// 2# Negotiate response (same object as given)
	Assert::same($response, $negotiation->negotiateResponse($request, $response));
});

// JSON negotiation (according to .json suffix in URL)
test(function () {
	$negotiation = new SuffixNegotiator(['.json' => new JsonTransformer()]);

	$request = Psr7ServerRequestFactory::fromSuperGlobal()->withNewUri('https://contributte.org/foo.json');
	$response = Psr7ResponseFactory::fromGlobal();
	$response = $response->writeJsonBody(['foo' => 'bar']);

	// 1# Negotiate request
	$request = $negotiation->negotiateRequest($request, $response);

	// 2# Negotiate response (PSR7 body contains encoded json data)
	$res = $negotiation->negotiateResponse($request, $response);
	Assert::equal('{"foo":"bar"}', (string) $res->getBody());
});

// Fallback negotiation (*)
test(function () {
	$negotiation = new SuffixNegotiator(['*' => new JsonTransformer()]);

	$request = Psr7ServerRequestFactory::fromSuperGlobal()->withNewUri('https://contributte.org/foo.bar');
	$response = Psr7ResponseFactory::fromGlobal();
	$response = $response->writeJsonBody(['foo' => 'bar']);

	// 1# Negotiate request
	$request = $negotiation->negotiateRequest($request, $response);

	// 2# Negotiate response (PSR7 body contains encoded json data)
	$res = $negotiation->negotiateResponse($request, $response);
	Assert::equal('{"foo":"bar"}', (string) $res->getBody());
});
