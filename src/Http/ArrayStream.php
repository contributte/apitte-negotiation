<?php

namespace Apitte\Negotiation\Http;

class ArrayStream extends DataStream
{

	/** @var array */
	private $content = [];

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

}
