<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tags;

use hydrogen\view\engines\hydrogen\Tag;
use hydrogen\view\engines\hydrogen\nodes\IfNode;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class IfTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
		if (empty($args))
			throw new TemplateSyntaxException("Tag 'if' requires an expression argument in template $origin.");
		$terminators = array('elseif', 'else', 'endif');
		$condChain = array();
		$elseHit = false;
		$condChain[] = array($args, $parser->parse($terminators));
		while (true) {
			$token = $parser->peekNextToken();
			$parser->skipNextToken();
			if ($token->cmd === 'endif')
				break;
			else if ($token->cmd === 'else') {
				if (!$token->args) {
					if ($elseHit) {
						throw new TemplateSyntaxException(
							'The if-statement in template "' . $origin .
							'" cannot have multiple "else" tags.');
					}
					$condChain[] = array(true, $parser->parse($terminators));
					$elseHit = true;
				}
				else {
					if ($elseHit) {
						throw new TemplateSyntaxException(
							'The if-statement in template "' . $origin .
							'" cannot have conditionals after an "else" tag.');
					}
					if (strpos(strtolower($token->args), 'if ') !== 0) {
						throw new TemplateSyntaxException(
							'The "else" section of an if-statement cannot have arguments in template "' .
							$origin . '".  Did you mean "else if"?');
					}
					$condChain[] = array(substr($token->args, 3),
						$parser->parse($terminators));
				}
			}
			else if ($token->cmd === 'elseif') {
				if ($elseHit) {
					throw new TemplateSyntaxException(
						'The if-statement in template "' . $origin .
						'" cannot have conditionals after an "else" tag.');
				}
				if (!$token->args) {
					throw new TemplateSyntaxException(
						'The elseif tag in template "' . $origin .
						'" requires a conditional.');
				}
				$condChain[] = array($token->args,
					$parser->parse($terminators));
			}
		}
		return new IfNode($condChain, $origin);
	}

}

?>