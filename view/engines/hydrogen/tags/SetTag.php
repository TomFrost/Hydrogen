<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tags;

use hydrogen\view\engines\hydrogen\Lexer;
use hydrogen\view\engines\hydrogen\Tag;
use hydrogen\view\engines\hydrogen\nodes\SetNode;
use hydrogen\view\engines\hydrogen\nodes\VariableNode;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class SetTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
		if (empty($args)) {
			throw new TemplateSyntaxException('Tag "set" in template ' .
				$origin . ' requires a variable name argument.');
		}
		$args = preg_split('/\s/', $args, 2, PREG_SPLIT_NO_EMPTY);
		$token = Lexer::getVariableToken($origin, $args[0]);
		if ($token->filters) {
			throw new TemplateSyntaxException(
				'Target variable in the "set" tag in template ' . $origin .
				'cannot have filters applied to it.');
		}
		$var = new VariableNode($token->varLevels, false, false, $origin);
		$toNative = false;
		$toVar = false;
		$toNodes = false;
		if (count($args) === 2) {
			if (Lexer::surroundedBy($args[1], '"', '"') ||
					is_numeric($args[1]) ||
					$args[1] === 'true' ||
					$args[1] === 'false') {
				$toNative = $args[1];
			}
			else {
				$token = Lexer::getVariableToken($origin, $args[1]);
				$toVar = new VariableNode($token->varLevels, $token->filters,
					false, $origin);
			}
		}
		else {
			$toNodes = $parser->parse('endset');
			$parser->skipNextToken();
		}
		return new SetNode($var, $toNative, $toVar, $toNodes);
	}

}

?>