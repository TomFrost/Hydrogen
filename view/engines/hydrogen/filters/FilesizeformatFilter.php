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
		if (count($args) > 1) {
			throw new TemplateSyntaxException(
				'The "filesizeformat" filter requires one or no arguments.');
		}
		$phpfile->addFunction('fileSizeFormatFilter',
				array('$size', '$decimals=2'), <<<'PHP'
			$size = (int)$size;
			if (($size = $size / 1024) < 1024){
				$size = number_format($size, $decimals);
				return $size . ' KB';
			} else if (($size = $size / 1024) < 1024) {
				$size = number_format($size, $decimals);
				return $size . ' MB';
			} else if (($size = $size / 1024) < 1024) {
				$size = number_format($size, $decimals);
				return $size . ' GB';
			}
PHP
		);
		$escape = false;
		if (count($args) === 0)
			return 'fileSizeFormatFilter(' . $string . ')';
		else {
			return 'fileSizeFormatFilter(' . $string . ', ' .
				$args[0]->getValue($phpfile) . ')';
		}
	}

}
?>