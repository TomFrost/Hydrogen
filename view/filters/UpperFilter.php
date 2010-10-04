<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\filters;

use hydrogen\view\Filter;

class UpperFilter implements Filter {

	public static function applyTo($string, $args, $context) {
		return strtoupper($string);
	}

}