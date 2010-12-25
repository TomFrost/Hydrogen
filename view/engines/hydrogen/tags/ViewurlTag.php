<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tags;

use hydrogen\view\engines\hydrogen\Lexer;
use hydrogen\view\engines\hydrogen\Tag;
use hydrogen\view\engines\hydrogen\nodes\ViewurlNode;

class ViewurlTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
		return new ViewurlNode($args);
	}

}

?>