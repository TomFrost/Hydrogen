<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class LinebreaksFilter implements Filter {

	public static function applyTo($string, $args, &$escape, $phpfile) {
		if ($escape === true) {
			$string = 'htmlentities(' . $string . ')';
			$escape = false;
		}
		return '(\'<p>\' . nl2br(preg_replace(\'/\s*\r?\n\s*\r?\n\s*/s\', \'</p><p>\', ' .
			$string . '), true) . \'</p>\')';
	}

}

?>