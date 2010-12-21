<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tags;

use hydrogen\view\engines\hydrogen\Tag;
use hydrogen\view\engines\hydrogen\nodes\BlockNode;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class AutoescapeTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
		if (($arg = strtolower($args)) === 'off')
			$parser->pushAutoescape(false);
		else if ($arg === 'on')
			$parser->pushAutoescape(true);
		else {
			throw new TemplateSyntaxException(
				'Autoescape tag requires either "on" or "off" as an argument.');
		}
		$nodes = $parser->parse("endautoescape");
		$parser->skipNextToken();
		$parser->popAutoescape();
		return new BlockNode($nodes);
	}

}

?>