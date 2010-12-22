<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;

class LowerFilter implements Filter {

	public static function applyTo($string, $args, $phpfile) {
		return 'strtolower(' . $string . ')';
	}

}