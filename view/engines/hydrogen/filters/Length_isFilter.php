<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class Length_isFilter implements Filter {

	public static function applyTo($string, $args, &$escape, $phpfile) {
		if (count($args) !== 1) {
			throw new TemplateSyntaxException(
				'The "length_is" filter requires exactly one argument.');
		}
		$escape = false;
		return '((is_array(' . $string . ') ? count(' . $string . ') : ' .
			'strlen(' . $string . ')) == ' .
			$args[0]->getValue($phpfile) . ')';
	}

}

?>