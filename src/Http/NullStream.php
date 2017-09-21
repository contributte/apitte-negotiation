<?php

namespace Apitte\Negotiation\Http;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

class NullStream implements StreamInterface
{

	public function getContents()
	{
		return NULL;
	}

	public function close()
	{
	}

	public function detach()
	{
		$this->close();
	}

	public function getSize()
	{
		return 0;
	}

	public function isReadable()
	{
		return FALSE;
	}

	public function isWritable()
	{
		return FALSE;
	}

	public function isSeekable()
	{
		return FALSE;
	}

	public function rewind()
	{
	}

	public function seek($offset, $whence = SEEK_SET)
	{
	}

	public function eof()
	{
		return TRUE;
	}

	public function tell()
	{
		throw new RuntimeException('Null streams cannot tell position');
	}

	public function read($length)
	{
		throw new RuntimeException('Null streams cannot read');
	}

	public function write($data)
	{
		throw new RuntimeException('Null streams cannot write');
	}

	public function getMetadata($key = NULL)
	{
		return NULL;
	}

	public function __toString()
	{
		return $this->getContents();
	}

}
