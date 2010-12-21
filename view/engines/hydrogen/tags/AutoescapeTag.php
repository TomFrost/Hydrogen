<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tags;

use hydrogen\view\engines\hydrogen\Tag;
use hydrogen\view\engines\hydrogen\nodes\AutoescapeNode;

class AutoescapeTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
		if (strtolower($args) === 'off')
			$parser->pushAutoescape(false);
		else
			$parser->pushAutoescape(true);
		$nodes = $parser->parse("endautoescape");
		$parser->skipNextToken();
		$parser->popAutoescape();
		return new BlockNode($nodes);
	}

}

?>