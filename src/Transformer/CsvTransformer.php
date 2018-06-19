<?php declare(strict_types = 1);

namespace Apitte\Negotiation\Transformer;

use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Throwable;

class CsvTransformer extends AbstractTransformer
{

	/**
	 * Encode given data for response
	 *
	 * @param mixed[] $context
	 */
	public function transform(ApiRequest $request, ApiResponse $response, array $context = []): ApiResponse
	{
		if (isset($context['exception'])) {
			return $this->transformException($context['exception'], $request, $response);
		}

		return $this->transformResponse($request, $response);
	}

	protected function transformException(Throwable $exception, ApiRequest $request, ApiResponse $response): ApiResponse
	{
		$content = sprintf('Exception occurred with message "%s"', $exception->getMessage());
		$response->getBody()->write($content);

		// Setup content type
		$response = $response
			->withStatus(500)
			->withHeader('Content-Type', 'text/plain');

		return $response;
	}

	protected function transformResponse(ApiRequest $request, ApiResponse $response): ApiResponse
	{
		$content = $this->convert($this->getEntity($response)->getData());
		$response->getBody()->write($content);

		// Setup content type
		$response = $response
			->withHeader('Content-Type', 'text/plain');

		return $response;
	}

	/**
	 * @param mixed[][] $rows
	 */
	private function convert(array $rows, string $delimiter = ',', string $enclosure = '"'): string
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
