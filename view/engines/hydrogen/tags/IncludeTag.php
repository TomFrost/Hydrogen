<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tags;

use hydrogen\view\engines\hydrogen\Tag;
use hydrogen\view\engines\hydrogen\Lexer;
use hydrogen\view\engines\hydrogen\nodes\IncludeNode;
use hydrogen\view\engines\hydrogen\nodes\VariableNode;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class IncludeTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
		if (empty($args) || strpos($args, ' ') !== false) {
			throw new TemplateSyntaxException(
				'Tag "include" in template "' . $origin .
				'" requires a single template name as an argument.');
		}
		if (Lexer::surroundedBy($args, '"', '"')) {
			$parser->prependPage(substr($args, 1, -1));
			return false;
		}
		$token = Lexer::getVariableToken($origin, $args);
		$node = new VariableNode($token->varLevels, $token->filters,
			false, $origin);
		return new IncludeNode($node);
	}

}

?>