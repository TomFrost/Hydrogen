<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;

class UnescapeFilter implements Filter {

	public static function applyTo($string, $args, &$escape, $phpfile) {
		$escape = false;
		return $string;
	}

}

?>