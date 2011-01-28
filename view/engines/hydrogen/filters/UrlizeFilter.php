<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class UrlizeFilter implements Filter {

	public static function applyTo($string, $args, &$escape, $phpfile) {
		$phpfile->addFunction('urlizeFilter', array('$str', '$esc'), <<<'PHP'
			if ($esc)
				$str = htmlentities($str);
			$str = preg_replace(
				'#(https?://[a-zA-Z0-9~\#\-\?\&_/]+(([.=;%][a-zA-Z0-9~\#\-\?\&_/]+)+)?)#',
				'<a href="$0">$0</a>', $str);
			if ($esc) {
				$pos = -1;
				while (($pos = strpos($str, '<a', $pos + 1)) !== false) {
					$end = strpos($str, '>', $pos) + 1;
					$sub = substr($str, $pos, $end - $pos);
					$fix = str_replace('&amp;', '&', $sub);
					$str = substr($str, 0, $pos) . $fix . substr($str, $end);
				}
			}
			return $str;
PHP
		);
		$string = 'urlizeFilter(' . $string . ', ' .
			($escape ? 'true' : 'false') . ')';
		$escape = false;
		return $string;
	}

}

?>