<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

/**
 * The ExcerptFilter creates an excerpt of a given variable. It accepts three
 * optional arguments: excerpt length (default: 20), excerpt 'mode'
 * (default: words), and the append string (default: [...])
 * 
 * The supported modes are:
 * - w (words)
 * - l (lines)
 * - c (characters)
 * 
 * If a variable is actually shortened by generating the excerpt, it is
 * appended with the append string.  By default, this is " [...]".  To change
 * the default, the following line can be added to the autoconfig with a new
 * value:
 *
 * <pre>
 * Config::setVal('view', 'excerpt_append', '&#8230');
 * </pre>
 *
 * Note that if escaping is on, the string will be escaped BEFORE the append
 * string is added, so the apprend string can contain HTML or entities.
 *
 * For special cases where the append string should be different than the
 * default, simply supply it as a third argument.
 * 
 * Example usage:
 * 
 * <pre>
 * {% set myVar %}
 *	 This is a very long Text.
 * {% endset %}
 * {{myVar|excerpt:5:"w"}} => This is a very long [...]
 * {{myVar|excerpt:12:"c":"--"}} => This is a ve--
 * {{myVar|excerpt:3:"l"}} => This is a very long Text.
 * </pre>
 * 
 * Note that the third case is not appended with an ellipsis, as the filtered
 * string is not shorter than the original.
 */
class ExcerptFilter implements Filter {
	
	const DEFAULT_APPEND_STRING = " [...]";

	public static function applyTo($string, $args, &$escape, $phpfile) {
		if (count($args) > 3) {
			throw new TemplateSyntaxException(
				'The "excerpt" filter supports only three arguments.');
		}
		$phpfile->addFunction('excerptFilter',
			array('$str', '$num', '$needle', '$append'), <<<'PHP'
			if ($str = trim($str)) {
				$cutpos = 0;
				if ($needle === false)
					$cutpos = $num;
				else {
					$steps = 0;
					$findpos = 0;
					while ($steps < $num && $findpos !== false) {
						$findpos = strpos($str, $needle, $findpos + 1);
						$steps++;
					}
					if ($findpos)
						$cutpos = $findpos;
				}
				if ($cutpos && strlen($str) > $cutpos)
					return substr($str, 0, $cutpos) . $append;
			}
			return $str;
PHP
		);
		$num = isset($args[0]) ? $args[0]->getValue($phpfile) : 20;
		$mode = isset($args[1]) ? trim($args[1]->getValue($phpfile), "'") : 'w';
		$append = isset($args[2]) ? $args[2]->getValue($phpfile) : false;
		if ($append === false) {
			$append = Config::getVal('view', 'excerpt_append') ?:
				self::DEFAULT_APPEND_STRING;
			$append = "'" . str_replace("'", '\\\'', $append) . "'";
		}
		$needle = false;
		switch($mode) {
		case 'l':
			$needle = '"\n"';
			break;
		case 'c':
			$needle = 'false';
			break;
		case 'w':
		default:
			$needle = '" "';
		}
		// Manually handle the escaping here just in case excerptFilter needs
		// to append something that can't be escaped.
		if ($escape) {
			$string = "htmlentities($string)";
			$escape = false;
		}
		return "excerptFilter($string, $num, $needle, $append)";
	}
}

?>
