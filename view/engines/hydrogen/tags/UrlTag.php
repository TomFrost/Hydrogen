<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tags;

use hydrogen\view\engines\hydrogen\Lexer;
use hydrogen\view\engines\hydrogen\Tag;
use hydrogen\view\engines\hydrogen\nodes\UrlNode;
use hydrogen\view\engines\hydrogen\nodes\VariableNode;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class UrlTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
		$folders = array();
		$kvPairs = array();
		$tokens = preg_split('/\s/', $args, 2, PREG_SPLIT_NO_EMPTY);
		if (isset($tokens[0])) {
			$folders = preg_split('/\//', $tokens[0], null,
				PREG_SPLIT_NO_EMPTY);
			if (isset($tokens[1])) {
				$tokens = preg_split('/[\s\/]/', $tokens[1], null,
					PREG_SPLIT_NO_EMPTY);
				foreach ($tokens as $token) {
					if (strpos($token, '=') !== false) {
						$keyVal = explode('=', $token);
						if (count($keyVal) !== 2 || !$keyVal[0] |
								!$keyVal[1]) {
							throw new TemplateSyntaxException(
								'URL tag parameter in template "' . $origin .
								'" contains invalid key=value pair: ' .
								$token);
						}
						if (Lexer::surroundedBy($keyVal[1], '"', '"'))
							$kvPairs[$keyVal[0]] = substr($keyVal[1], 1, -1);
						else {
							$vToken = Lexer::getVariableToken($origin,
								$keyVal[1]);
							$vNode = new VariableNode($vToken->varLevels,
								$vToken->filters, false, $origin);
							$kvPairs[$keyVal[0]] = $vNode;
						}
					}
					else {
						if (Lexer::surroundedBy($token, '"', '"'))
							$folders[] = substr($token, 1, -1);
						else {
							$vToken = Lexer::getVariableToken($origin, $token);
							$vNode = new VariableNode($vToken->varLevels,
								$vToken->filters, false, $origin);
							$folders[] = $vNode;
						}
					}
				}
			}
		}
		return new UrlNode($folders, $kvPairs);
	}

}

?>