<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tags;

use hydrogen\view\engines\hydrogen\Tag;
use hydrogen\view\engines\hydrogen\nodes\BlockNode;

class CommentTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
		$nodes = $parser->parse("endcomment");
		$parser->skipNextToken();
		return false;
	}

}

?>