<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class DivisiblebyFilter implements Filter {

	public static function applyTo($string, $args, &$escape, $phpfile) {
		if (count($args) !== 1) {
			throw new TemplateSyntaxException(
				'The "divisibleby" filter requires exactly one argument.');
		}
		$escape = false;
		return '(((int)' . $string . ')/' . $args[0]->getValue($phpfile) .
			'===((int)(' . $string . '/' . $args[0]->getValue($phpfile) .
			')))';
	}

}

?>