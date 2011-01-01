<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class PluralizeFilter implements Filter {

	public static function applyTo($string, $args, &$escape, $phpfile) {
		$numArgs = count($args);
		if ($numArgs > 2) {
			throw new TemplateSyntaxException(
				'The "pluralize" filter accepts a maximum of two arguments.');
		}
		$escape = false;
		$singular = '\'\'';
		$plural = '\'s\'';
		if ($numArgs === 1)
			$plural = $args[0]->getValue($phpfile);
		else if ($numArgs === 2) {
			$singular = $args[0]->getValue($phpfile);
			$plural = $args[1]->getValue($phpfile);
		}
		return '((is_array(' . $string . ') && count(' . $string .
			') === 1) || (is_numeric(' . $string . ') && ' . $string .
			' == 1) ?' . $singular . ':' . $plural . ')';
	}

}

?>