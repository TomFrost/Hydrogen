<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tokens;

use hydrogen\view\engines\hydrogen\Lexer;
use hydrogen\view\engines\hydrogen\Token;

class BlockToken extends Token {
	// Reflection to get the class name is slow, but constants are fast :)
	const TOKEN_TYPE = Lexer::TOKEN_BLOCK;

	public $cmd;
	public $args;

	public function __construct($origin, $raw, $cmd, $args) {
		$this->origin = &$origin;
		$this->raw = &$raw;
		$this->cmd = &$cmd;
		$this->args = &$args;
	}
}

?>