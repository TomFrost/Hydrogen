<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\tags;

use hydrogen\view\Tag;

class ExtendsTag extends Tag {
	
	public static function getNode($origin, $data, $parser) {
		$parser->setOriginParent($origin, $data);
		$parser->prependPage($data);
		return false;
	}
	
	public static function mustBeFirst() {
		return true;
	}
}

?>