<?php

namespace Apitte\Negotiation\Transformer;

use Apitte\Core\Exception\Logical\InvalidArgumentException;
use Apitte\Negotiation\Http\CsvStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CsvTransformer extends AbstractTransformer
{

	/**
	 * Encode given data for response
	 *
	 * @param ResponseInterface $response
	 * @param array $options
	 * @return ResponseInterface
	 */
	public function encode(ResponseInterface $response, array $options = [])
	{
		// Return immediately if response is not accepted
		if (!($response->getBody() instanceof CsvStream))
			return $response;

		/** @var CsvStream $body */
		$body = $response->getBody();
		$originBody = $body->getOriginal()->getBody();
		$csv = $this->convert($body->getData());
		$originBody->write($csv);

		// Setup content type
		$response = $response
			->withBody($originBody)
			->withHeader('Content-Type', 'text/csv');

		return $response;
	}

	/**
	 * Parse given data from request
	 *
	 * @param ServerRequestInterface $request
	 * @param array $options
	 * @return NULL
	 */
	public function decode(ServerRequestInterface $request, array $options = [])
	{
		return NULL;
	}

	/**
	 * @param array[] $rows
	 * @param string $delimiter
	 * @param string $enclosure
	 * @return string
	 */
	private function convert($rows, $delimiter = ',', $enclosure = '"')
	{
		$fp = fopen('php://temp', 'r+');
		foreach ($rows as $row) {
			foreach ($row as $item) {
				if (is_array($item) || !is_scalar($item)) {
					throw new InvalidArgumentException('CSV need flat array');
				}
			}
			fputcsv($fp, $row, $delimiter, $enclosure);
		}
		rewind($fp);
		$data = fread($fp, 1048576);
		fclose($fp);
		return rtrim($data, "\n");
	}

}
