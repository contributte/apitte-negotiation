<?php

namespace Apitte\Negotiation\Http;

use Psr\Http\Message\ResponseInterface;

class ArrayStream extends NullStream
{

	/** @var array */
	private $content = [];

	/** @var ResponseInterface */
	private $original;

	/**
	 * @param array $content
	 */
	private function __construct(array $content = [])
	{
		$this->content = $content;
	}

	/**
	 * @return array
	 */
	public function getData()
	{
		return $this->content;
	}

	/**
	 * @param array $content
	 * @return static
	 */
	public function with(array $content)
	{
		$this->content = $content;

		return $this;
	}

	/**
	 * @return ResponseInterface
	 */
	public function getOriginal()
	{
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
		$stream = new static([]);
		$stream->original = $response;

		return $stream;
	}

}
