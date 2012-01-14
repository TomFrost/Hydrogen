<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class LinebreaksbrFilter implements Filter {

	public static function applyTo($string, $args, &$escape, $phpfile) {
		if ($escape === true) {
			$string = 'htmlentities(' . $string . ')';
			$escape = false;
		}
		return 'nl2br(' . $string . ', true)';
	}

}

?>