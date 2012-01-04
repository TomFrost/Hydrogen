<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class SlugifyFilter implements Filter {

	public static function applyTo($string, $args, &$escape, $phpfile) {
		$escape = false;
		return 'strtolower(str_replace(\' \', \'-\', preg_replace(\'/[^a-zA-Z0-9_\s]/\', \'\', trim(' .
			$string . '))))';
	}

}

?>