<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\tokens;

use hydrogen\view\Lexer;
use hydrogen\view\Token;

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