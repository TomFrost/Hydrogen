<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tokens;

use hydrogen\view\engines\hydrogen\Lexer;
use hydrogen\view\engines\hydrogen\Token;

class CommentToken extends Token {
	// Reflection to get the class name is slow, but constants are fast :)
	const TOKEN_TYPE = Lexer::TOKEN_COMMENT;
}

?>