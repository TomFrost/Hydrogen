<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tags;

use hydrogen\view\engines\hydrogen\Tag;
use hydrogen\view\engines\hydrogen\nodes\BlockNode;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class ParentblockTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
		$blockName = $parser->stackPeek('block');
		if ($blockName == null) {
			throw new TemplateSyntaxException(
				'The "parentblock" tag can only be used inside of block tags for an extended block in "' .
				$origin . "'.");
		}
		$block = $parser->getObject($blockName);
		if ($block === false) {
			throw new TemplateSyntaxException(
				'The "parentblock" tag can only be used inside of an extended block in "' .
				$origin . "'.");
		}
		return clone $block;
	}

}

?>