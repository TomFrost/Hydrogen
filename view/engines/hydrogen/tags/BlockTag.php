<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tags;

use hydrogen\view\engines\hydrogen\Tag;
use hydrogen\view\engines\hydrogen\nodes\BlockNode;

class BlockTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
		$parser->stackPush('block', $args);
		$nodes = $parser->parse("endblock");
		$parser->skipNextToken();
		$parser->stackPop('block');
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