<?php

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class PercentFilter implements Filter {

	public static function applyTo($string, $args, &$escape, $phpfile) {
		if (count($args) !== 1)
			throw new TemplateSyntaxException("The 'percent' filter requires exactly one argument.");
		$escape = false;
		return 'number_format('. $string . ' * 100,' . $args[0]->getValue($phpfile) . ",'.',',') . '%'";
	}
}

?>