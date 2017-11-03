<?php

namespace Apitte\Negotiation\Http;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class ArrayEntity extends AbstractEntity implements IteratorAggregate, Countable
{

	/**
	 * @param array $data
	 */
	public function __construct(array $data)
	{
		parent::__construct($data);
	}

	/**
	 * @param array $data
	 * @return static
	 */
	public static function from(array $data)
	{
		return new static($data);
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return (array) $this->getData();
	}

	/**
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->toArray());
	}

	/**
	 * @return int
	 */
	public function count()
	{
		return count($this->toArray());
	}

}
