<?php

namespace Apitte\Negotiation;

use Apitte\Negotiation\Transformer\ITransformer;

class NegotiationFactory
{

	/**
	 * @param ITransformer[] $transformers
	 * @return SuffixNegotiator
	 */
	public static function bySuffix(array $transformers)
	{
		return new SuffixNegotiator($transformers);
	}

}
