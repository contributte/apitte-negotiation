<?php

/**
 * Test: Transformer\CsvTransformer
 */

require_once __DIR__ . '/../../bootstrap.php';

use Apitte\Negotiation\Http\CsvStream;
use Apitte\Negotiation\Transformer\CsvTransformer;
use Contributte\Psr7\Psr7Response;
use Contributte\Psr7\Psr7ResponseFactory;
use Tester\Assert;

// Encode
test(function () {
	$transformer = new CsvTransformer();
	$response = Psr7ResponseFactory::fromGlobal();
	$response = $response->withBody(CsvStream::from($response)->withRows([
		['1', '2', '3'],
		['4', '5', '6'],
		['7', '8', '9'],
	])->withHeader(['A', 'B', 'C']));

	/** @var Psr7Response $response */
	$response = $transformer->encode($response);
	$response->getBody()->rewind();

	Assert::equal('A,B,C
1,2,3
4,5,6
7,8,9', $response->getContents());
});
