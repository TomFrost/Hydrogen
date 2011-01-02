<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class FilesizeformatFilter implements Filter {

	public static function applyTo($string, $args, &$escape, $phpfile) {
		$phpfile->addFunction('parseFileSize', array('$size'), <<<'PHP'
			$size = (int)$size;
			if (($size = $size / 1024) < 1024){
				$size = number_format($size, 2);
				return $size . ' KB';
			} else if (($size = $size / 1024) < 1024) {
				$size = number_format($size, 2);
				return $size . ' MB';
			} else if (($size = $size / 1024) < 1024) {
				$size = number_format($size, 2);
				return $size . ' GB';
			}
PHP
		);
		$escape = false;
		return 'parseFileSize(' . $string . ')';
	}

}
?>