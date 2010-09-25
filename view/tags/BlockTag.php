<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\tags;

use hydrogen\view\Tag;
use hydrogen\view\nodes\BlockNode;

class BlockTag implements Tag {
	
	public static function getNode($origin, $data, $parser) {
		$nodes = $parser->parse("endblock");
		$parser->skipNextToken();
		$block = $parser->getObject($data);
		if ($block) {
			$block->setNodes($nodes);
			return false;
		}
		else {
			$block = new BlockNode($nodes);
			$parser->registerObject($data, $block);
			return $block;
		}
	}
	
}

?>