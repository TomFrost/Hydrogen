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
	const TOKEN_BLOCK = 2;
	const TOKEN_COMMENT = 3;
	
	const VARIABLE_OPENTAG = "{{";
	const VARIABLE_CLOSETAG = "}}";
	const BLOCK_OPENTAG = "{%";
	const BLOCK_CLOSETAG = "%}";
	const COMMENT_OPENTAG = "{#";
	const COMMENT_CLOSETAG = "#}";
	
	public static function tokenize($data) {
		$splitRegex = '/(' .
			self::VARIABLE_OPENTAG . '.*' . self::VARIABLE_CLOSETAG . '|' .
			self::BLOCK_OPENTAG . '.*' . self::BLOCK_CLOSETAG . '|' .
			self::COMMENT_OPENTAG . '.*' . self::COMMENT_CLOSETAG .
			')/U';
		$lines = preg_split($splitRegex, $data, null,
			PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		
		$tokens = array();
		foreach ($lines as $line) {
			// Check for variable tag
			if (static::surroundedBy($line, self::VARIABLE_OPENTAG,
				self::VARIABLE_CLOSETAG)) {
				$tokens[] = new Token(self::TOKEN_VARIABLE,
					trim(substr($line, strlen(self::VARIABLE_OPENTAG),
					strlen($line) - strlen(self::VARIABLE_OPENTAG) -
					strlen(self::VARIABLE_CLOSETAG))));
			}
			// Check for block tag
			else if (static::surroundedBy($line, self::BLOCK_OPENTAG,
				self::BLOCK_CLOSETAG)) {
				$tokens[] = new Token(self::TOKEN_BLOCK,
					trim(substr($line, strlen(self::BLOCK_OPENTAG),
					strlen($line) - strlen(self::BLOCK_OPENTAG) -
					strlen(self::BLOCK_CLOSETAG))));
			}
			// Check for comment tag
			else if (static::surroundedBy($line, self::COMMENT_OPENTAG,
				self::COMMENT_CLOSETAG)) {
				$tokens[] = new Token(self::TOKEN_COMMENT,
					trim(substr($line, strlen(self::COMMENT_OPENTAG),
					strlen($line) - strlen(self::COMMENT_OPENTAG) -
					strlen(self::COMMENT_CLOSETAG))));
			}
			// It must be text!  But skip it if it's empty.
			else if (($text = trim($line)) !== '')
				$tokens[] = new Token(self::TOKEN_TEXT, $text);
		}
		return $tokens;
	}
	
	protected static function surroundedBy($haystack, $startsWith, $endsWith) {
		$sLen = strlen($startsWith);
		$eLen = strlen($endsWith);
		if (strlen($haystack) >= $sLen + $eLen) {
			return substr_compare($haystack, $startsWith, 0, $sLen) === 0 &&
				substr_compare($haystack, $endsWith, -$eLen, $eLen) === 0;
		}
		return false;
	}

	protected function __construct() {}
}

?>