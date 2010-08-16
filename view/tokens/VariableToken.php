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
	
	public $variable;
	public $drillDowns;
	public $filters;
	
	public function __construct($origin, $raw, $variable, $drillDowns,
			$filters) {
		$this->origin = &$origin;
		$this->raw = &$raw;
		$this->variable = &$variable;
		$this->drillDowns = &$drillDowns;
		$this->filters = &$filters;
	}
}

?>