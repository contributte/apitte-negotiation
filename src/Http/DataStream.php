<?php

namespace Apitte\Negotiation\Http;

use Apitte\Core\Exception\Logical\InvalidStateException;
use Contributte\Psr7\NullStream;
use Psr\Http\Message\ResponseInterface;

abstract class DataStream extends NullStream
{

	/** @var ResponseInterface */
	protected $original;

	/**
	 * Create ArrayStream
	 */
	private function __construct()
	{
	}

	/**
	 * @return ResponseInterface
	 */
	public function getOriginal()
	{
		if ($this->original === NULL) {
			throw new InvalidStateException('Missing original response');
		}

		// Unwrap to original
		if ($this->original->getBody() instanceof DataStream) {
			return $this->original->getBody()->getOriginal();
		}

		return $this->original;
	}

	/**
	 * FACTORIES ***************************************************************
	 */

	/**
	 * @param ResponseInterface $response
	 * @return static
	 */
	public static function from(ResponseInterface $response)
	{
		$stream = new static();
		$stream->original = $response;

		return $stream;
	}

}
