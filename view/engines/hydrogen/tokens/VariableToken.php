<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tokens;

use hydrogen\view\engines\hydrogen\Lexer;
use hydrogen\view\engines\hydrogen\Token;

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