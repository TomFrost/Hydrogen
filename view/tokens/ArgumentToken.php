<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\tokens;

use hydrogen\view\Lexer;
use hydrogen\view\Token;

class ArgumentToken extends Token {
	// Reflection to get the class name is slow, but constants are fast :)
	const TOKEN_TYPE = Lexer::TOKEN_ARGUMENT;
	const ARG_VARIABLE = 0;
	const ARG_NATIVE = 1;
	
	public $data;
	public $type;
	
	public function __construct($origin, $raw, $type, $data) {
		$this->origin = &$origin;
		$this->raw = &$raw;
		$this->type = &$type;
		$this->data = &$data;
	}
}

?>