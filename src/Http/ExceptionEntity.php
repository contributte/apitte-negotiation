<?php declare(strict_types = 1);

namespace Apitte\Negotiation\Http;

use Throwable;

class ExceptionEntity extends AbstractEntity
{

	/** @var Throwable */
	protected $exception;

	public function __construct(Throwable $exception)
	{
		parent::__construct();
		$this->exception = $exception;
	}

	public function getException(): Throwable
	{
		return $this->exception;
	}

}
