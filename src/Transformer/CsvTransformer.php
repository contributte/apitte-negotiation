<?php

namespace Apitte\Negotiation\Transformer;

use Apitte\Mapping\Http\ApiRequest;
use Apitte\Mapping\Http\ApiResponse;

class CsvTransformer extends AbstractTransformer
{

	/**
	 * Encode given data for response
	 *
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @param array $context
	 * @return ApiResponse
	 */
	public function transform(ApiRequest $request, ApiResponse $response, array $context = [])
	{
		// Return immediately if response is not accepted
		if (!$this->accept($response)) return $response;

		// Convert data to array to CSV
		$content = $this->convert($response->getEntity()->toArray());
		$response->getBody()->write($content);

		// Setup content type
		$response = $response
			->withHeader('Content-Type', 'text/plain');

		return $response;
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
					return 'CSV need flat array';
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
