<?php
/**
 * Copyright 2011 Koen Klaren
 * Made for use with Hydrogen, Copyright Tom Frost 2011
 * --------------------------
 * Takes a filesize in bytes and returns a nicely formatted size:
 * KB, MB or GB
 */

namespace hydrogen\view\engines\hydrogen\filters;

use hydrogen\view\engines\hydrogen\Filter;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class FilesizeformatFilter implements Filter {

	public static function applyTo($string, $args, &$escape, $phpfile) {
		$phpfile->addFunction('parseFileSize', array('$size'), '{
			$size = (int)$size;
			if (($size = $size / 1024) < 1024){
				$size = number_format($size, 2);
				return $size . \' KB\';
			} else if (($size = $size / 1024) < 1024) {
				$size = number_format($size, 2);
				return $size . \' MB\';
			} else if (($size = $size / 1024) < 1024) {
				$size = number_format($size, 2);
				return $size . \' GB\';
			}}');
		$string = '(parseFileSize(' . $string . '))';
		$escape = false;
		
		return $string;
	}

}
?>