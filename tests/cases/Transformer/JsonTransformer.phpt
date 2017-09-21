<?php

/**
 * Test: Transformer\JsonTransformer
 */

require_once __DIR__ . '/../../bootstrap.php';

use Apitte\Negotiation\Transformer\JsonTransformer;
use Contributte\Psr7\Psr7Response;
use Contributte\Psr7\Psr7ResponseFactory;
use Contributte\Psr7\Psr7ServerRequestFactory;
use Tester\Assert;
use function GuzzleHttp\Psr7\stream_for;

// Encode
test(function () {
	$transformer = new JsonTransformer();
	$response = Psr7ResponseFactory::fromGlobal();
	$response = $response->writeJsonBody(['foo' => 'bar']);

	/** @var Psr7Response $response */
	$response = $transformer->encode($response);
	$response->getBody()->rewind();

	Assert::equal('{"foo":"bar"}', $response->getContents());
});

// Decode
test(function () {
	$transformer = new JsonTransformer();
	$request = Psr7ServerRequestFactory::fromSuperGlobal()
		->withBody(stream_for('{"foo":"bar"}'));

	Assert::equal(['foo' => 'bar'], $transformer->decode($request)->getParsedBody());
});
