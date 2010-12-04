<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tags;

use hydrogen\view\engines\hydrogen\Tag;
use hydrogen\view\engines\hydrogen\nodes\BlockNode;

class BlockTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
		$nodes = $parser->parse("endblock");
		$parser->skipNextToken();
		$block = $parser->getObject($args);
		if ($block) {
			$block->setNodes($nodes);
			return false;
		}
		else {
			$block = new BlockNode($nodes);
			$parser->registerObject($args, $block);
			return $block;
		}
	}

}

?>