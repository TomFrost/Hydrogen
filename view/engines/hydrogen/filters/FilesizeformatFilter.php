<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
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
			$type = '';
			if ($size < 1024)
				$type = 'bytes';
			else if (($size /= 1024) < 1024)
				$type = 'KB';
			else if (($size /= 1024) < 1024)
				$type = 'MB';
			else if (($size /= 1024) < 1024)
				$type = 'GB';
			else {
				$size /= 1024;
				$type = 'TB';
			}
			$size = (string)number_format($size, $decimals);
			while (($char = $size[strlen($size) - 1]) === '0' || $char === '.')
				if(!isset($size[1]))
					break;
				$size = substr($size, 0, -1);
			}
			return $size . ' ' . $type;
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