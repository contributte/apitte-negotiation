<?php

namespace Apitte\Negotiation\Transformer;

use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Exception;

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
		if (isset($context['exception'])) {
			return $this->transformException($context['exception'], $request, $response);
		}

		return $this->transformResponse($request, $response);
	}

	/**
	 * @param Exception $exception
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @return ApiResponse
	 */
	protected function transformException(Exception $exception, ApiRequest $request, ApiResponse $response)
	{
		$content = sprintf('Exception occurred with message "%s"', $exception->getMessage());
		$response->getBody()->write($content);

		// Setup content type
		$response = $response
			->withStatus(500)
			->withHeader('Content-Type', 'text/plain');

		return $response;
	}

	/**
	 * @param ApiRequest $request
	 * @param ApiResponse $response
	 * @return ApiResponse
	 */
	protected function transformResponse(ApiRequest $request, ApiResponse $response)
	{
		$content = $this->convert($this->getEntity($response)->getData());
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
