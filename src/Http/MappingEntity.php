<?php

namespace Apitte\Negotiation\Http;

use Apitte\Core\Mapping\Response\IResponseEntity;

class MappingEntity extends AbstractEntity
{

	/** @var IResponseEntity */
	protected $entity;

	/**
	 * @param IResponseEntity $entity
	 */
	public function __construct(IResponseEntity $entity)
	{
		parent::__construct();
		$this->entity = $entity;
	}

	/**
	 * @return array
	 */
	public function getData()
	{
		return $this->entity->toResponse();
	}

	/**
	 * @return IResponseEntity
	 */
	public function getEntity()
	{
		return $this->entity;
	}

	/**
	 * @param IResponseEntity $value
	 * @return static
	 */
	public static function from(IResponseEntity $entity)
	{
		return new static($entity);
	}

}
