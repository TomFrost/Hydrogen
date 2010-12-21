<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tags;

use hydrogen\view\engines\hydrogen\Lexer;
use hydrogen\view\engines\hydrogen\Tag;
use hydrogen\view\engines\hydrogen\nodes\FilterNode;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class FilterTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
		$var = 'filter' . Lexer::VARIABLE_FILTER_SEPARATOR . $args;
		$token = Lexer::getVariableToken($origin, $var);
		$nodes = $parser->parse("endfilter");
		$parser->skipNextToken();
		return new FilterNode($nodes, $token->filters, $origin);
	}

}

?>