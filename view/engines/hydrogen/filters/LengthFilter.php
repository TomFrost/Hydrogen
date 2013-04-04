<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class LengthFilter implements Filter {

	public static function applyTo($string, $args, &$escape, $phpfile) {
		$escape = false;
		return '(is_array(' . $string . ') || (' . $string .
			' instanceof \ArrayObject) ? count(' . $string . ') : ' .
			'strlen(' . $string . '))';
	}

}

?>