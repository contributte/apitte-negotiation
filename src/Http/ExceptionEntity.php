<?php declare(strict_types = 1);

namespace Apitte\Negotiation\Http;

use Exception;
use Throwable;

class ExceptionEntity extends AbstractEntity
{

	/** @var Exception */
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
