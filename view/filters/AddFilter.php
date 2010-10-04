<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\filters;

use hydrogen\view\Filter;
use hydrogen\view\exceptions\TemplateSyntaxException;

class AddFilter implements Filter {

	public static function applyTo($string, $args, $context) {
		if (count($args) === 0)
			throw new TemplateSyntaxException("The 'add' filter requires at least one argument.");

		foreach ($args as $arg)
			$string += $arg->getValue($context);
		return $string;
	}

}