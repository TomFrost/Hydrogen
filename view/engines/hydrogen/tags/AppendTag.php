<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tags;

use hydrogen\view\engines\hydrogen\Tag;
use hydrogen\view\engines\hydrogen\Lexer;
use hydrogen\view\engines\hydrogen\nodes\AppendNode;
use hydrogen\view\engines\hydrogen\nodes\VariableNode;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;
use hydrogen\config\Config;

class AppendTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
		if (empty($args) || strpos($args, ' ') !== false) {
			throw new TemplateSyntaxException(
				'Tag "append" in template "' . $origin .
				'" requires a single file name as an argument.');
		}
		$file = Config::getBasePath() . '/' . Config::getVal("view", "folder") . '/' . $args;
		if (!file_exists($file)) {
			throw new TemplateSyntaxException(
				'Tag "append" in template "' . $origin .
				'" requires the file specified exists.');
		}
		return new AppendNode($file);
	}

}

?>