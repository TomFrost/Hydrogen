<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tags;

use hydrogen\view\engines\hydrogen\Tag;

class ExtendsTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
		$parser->setOriginParent($origin, $args);
		$parser->prependPage($args);
		return false;
	}

	public static function mustBeFirst() {
		return true;
	}
}

?>