<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

use hydrogen\view\Token;

class Lexer {
	const TOKEN_TEXT = 0;
	const TOKEN_VARIABLE = 1;
	const TOKEN_TAG = 2;
	
	public static function tokenize($data) {
		$tokens = array();
		return $tokens;
	}

	protected function __construct() {}
}

?>