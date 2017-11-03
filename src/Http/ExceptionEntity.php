<?php

namespace Apitte\Negotiation\Http;

use Exception;

class ExceptionEntity extends AbstractEntity
{

	/** @var Exception */
	protected $exception;

	/**
	 * @param Exception $exception
	 */
	public function __construct(Exception $exception)
	{
		parent::__construct();
		$this->exception = $exception;
	}

	/**
	 * @return Exception
	 */
	public function getException()
	{
		return $this->exception;
	}

}
