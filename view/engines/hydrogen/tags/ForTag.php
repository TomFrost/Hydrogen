<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tags;

use hydrogen\view\engines\hydrogen\Lexer;
use hydrogen\view\engines\hydrogen\Tag;
use hydrogen\view\engines\hydrogen\nodes\ForNode;
use hydrogen\view\engines\hydrogen\nodes\VariableNode;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class ForTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
		$tokens = preg_split('/\s/', $args, null, PREG_SPLIT_NO_EMPTY);
		$numTokens = count($tokens);
		$keyVar = false;
		$valVar = false;
		$arrayVar = false;
		$isKeyVal = false;
		// First token must be a variable.
		$token = array_shift($tokens);
		if ($token && ($lower = strtolower($token)) !== 'in' &&
				$lower !== 'status') {
			// If the token ends in a comma, it's definitely a k/v pair
			if ($token[strlen($token) - 1] === ',') {
				$keyVar = static::toVarNode(substr($token, 0, -1),
					$origin, true);
				$isKeyVal = true;
			}
			// If token contains a comma, we have both the k and v right here
			else if ($pos = strpos($token, ',')) {
				$keyVar = static::toVarNode(substr($token, 0, $pos), $origin,
					true);
				$valVar = static::toVarNode(substr($token, $pos + 1), $origin,
					true);
			}
			// All we have's a key.
			else
				$valVar = static::toVarNode($token, $origin, true);
		}
		else
			static::formatError($origin);
		// Next token might be a comma.
		$token = array_shift($tokens);
		if ($token && !$isKeyVal && $token === ',') {
			$keyVar = $valVar;
			$isKeyVal = true;
			$token = array_shift($tokens);
		}
		// Next token needs to be a variable if there was a comma
		if ($token && $isKeyVal) {
			$lower = strtolower($token);
			if ($lower !== 'in' && $lower !== 'status') {
				$valVar = static::toVarNode($token, $origin, true);
				$token = array_shift($tokens);
			}
			else
				static::formatError($origin);
		}
		// Next token absolutely must be 'in'
		if ($token && $token === 'in')
			$token = array_shift($tokens);
		else
			static::formatError($origin);
		// Next token must be a variable
		if ($token) {
			$arrayVar = static::toVarNode($token, $origin);
			$token = array_shift($tokens);
		}
		else
			static::formatError($origin);
		// If there's another token, that's an issue.
		if ($token)
			static::formatError($origin);
		// Arguments are legal!  Start with the parsing.
		$forNodes = $parser->parse(array('empty', 'endfor'));
		$token = $parser->peekNextToken();
		$parser->skipNextToken();
		// If there's an empty clause, parse again.
		if ($token->cmd === 'empty') {
			$emptyNodes = $parser->parse('endfor');
			$parser->skipNextToken();
		}
		else
			$emptyNodes = false;
		
		// Got it all, make a node.
		return new ForNode($keyVar, $valVar, $arrayVar, $forNodes,
			$emptyNodes);
	}

	protected static function toVarNode($var, $origin, $limitLevels=false) {
		$token = Lexer::getVariableToken($origin, $var);
		if ($token->filters) {
			throw new TemplateSyntaxException(
				'Variables in the "for" tag cannot contain filters in template "' .
				$origin . '".');
		}
		if ($limitLevels && count($token->varLevels) > 1) {
			throw new TemplateSyntaxException(
				'Target variables in the "for" tag in template "' . $origin .
				'" cannot have more than one level.');
		}
		return new VariableNode($token->varLevels, false, false, $origin);
	}

	protected static function formatError($origin) {
		throw new TemplateSyntaxException(
			'The "for" tag in template "' . $origin .
			'" must follow the format "for variable[, variable] in variable".');
	}
}

?>