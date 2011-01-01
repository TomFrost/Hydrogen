<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tokens;

use hydrogen\view\engines\hydrogen\Lexer;
use hydrogen\view\engines\hydrogen\Token;

class FilterToken extends Token {
	// Reflection to get the class name is slow, but constants are fast :)
	const TOKEN_TYPE = Lexer::TOKEN_FILTER;

	public $filter;
	public $args;

	public function __construct($origin, $raw, $filter, $args) {
		$this->origin = &$origin;
		$this->raw = &$raw;
		$this->filter = &$filter;
		$this->args = &$args;
	}
}

?>