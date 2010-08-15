<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\tokens;

use hydrogen\view\Lexer;
use hydrogen\view\Token;

class VariableToken extends Token {
	// Reflection to get the class name is slow, but constants are fast :)
	const TOKEN_TYPE = Lexer::TOKEN_VARIABLE;
	
	public $origin;
	public $raw;
	public $varStack;
	public $filters;
	
	public function __construct($origin, $raw, $varStack, $filters=false) {
		$this->origin = &$origin;
		$this->raw = &$raw;
		$this->varStack = is_array($varStack) ? $varStack : array($varStack);
		$this->filters = &$filters;
	}
}

?>