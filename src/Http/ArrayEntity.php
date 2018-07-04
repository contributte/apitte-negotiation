<?php declare(strict_types = 1);

namespace Apitte\Negotiation\Http;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class ArrayEntity extends AbstractEntity implements IteratorAggregate, Countable
{

	/**
	 * @param mixed[] $data
	 */
	public function __construct(array $data)
	{
		parent::__construct($data);
	}

	/**
	 * @param mixed[] $data
	 */
	public static function from(array $data): self
	{
		return new static($data);
	}

	/**
	 * @return mixed[]
	 */
	public function toArray(): array
	{
		return (array) $this->getData();
	}

	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->toArray());
	}

	public function count(): int
	{
		return count($this->toArray());
	}

}
