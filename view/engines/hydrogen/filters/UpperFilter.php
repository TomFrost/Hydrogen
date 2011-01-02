<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;

class UpperFilter implements Filter {

	public static function applyTo($string, $args, &$escape, $phpfile) {
		return 'strtoupper(' . $string . ')';
	}

}

?>