<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class YesnoFilter implements Filter {

	public static function applyTo($string, $args, &$escape, $phpfile) {
		if (count($args) !== 2) {
			throw new TemplateSyntaxException(
				'The "yesno" filter requires exactly two arguments.');
		}
		$escape = false;
		return '(' . $string . '?' . $args[0]->getValue($phpfile) . ':' .
			$args[1]->getValue($phpfile) . ')';
	}

}

?>