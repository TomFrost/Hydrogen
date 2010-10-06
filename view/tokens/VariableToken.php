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

	public $varLevels;
	public $filters;

	public function __construct($origin, $raw, $varLevels, $filters) {
		$this->origin = &$origin;
		$this->raw = &$raw;
		$this->varLevels = &$varLevels;
		$this->filters = &$filters;
	}
}

?>