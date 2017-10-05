<?php

/**
 * Test: Transformer\JsonTransformer
 */

require_once __DIR__ . '/../../bootstrap.php';

use Apitte\Mapping\Http\ApiRequest;
use Apitte\Mapping\Http\ApiResponse;
use Apitte\Negotiation\Transformer\JsonTransformer;
use Contributte\Psr7\Psr7ResponseFactory;
use Contributte\Psr7\Psr7ServerRequestFactory;
use Tester\Assert;
use function GuzzleHttp\Psr7\stream_for;

// Encode
test(function () {
	$transformer = new JsonTransformer();
	$response = new ApiResponse(Psr7ResponseFactory::fromGlobal());
	$response = $response->writeJsonBody(['foo' => 'bar']);

	$response = $transformer->encode($response);
	$response->getBody()->rewind();

	Assert::equal('{"foo":"bar"}', $response->getContents());
});

// Decode
test(function () {
	$transformer = new JsonTransformer();
	$request = new ApiRequest(Psr7ServerRequestFactory::fromSuperGlobal()
		->withBody(stream_for('{"foo":"bar"}')));

	Assert::equal(['foo' => 'bar'], $transformer->decode($request)->getParsedBody());
});
