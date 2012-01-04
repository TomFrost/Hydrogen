<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class DateFilter implements Filter {

	public static function applyTo($string, $args, &$escape, $phpfile) {
		if (count($args) !== 1) {
			throw new TemplateSyntaxException(
				'The "date" filter requires exactly one argument.');
		}
		return '(($dateFilter = strtotime(' . $string . ')) ? date(' .
			$args[0]->getValue($phpfile) . ', $dateFilter) : ' . $string . ')';
	}

}

?>