<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

use hydrogen\view\Token;
use hydrogen\view\tokens\BlockToken;
use hydrogen\view\tokens\CommentToken;
use hydrogen\view\tokens\FilterToken;
use hydrogen\view\tokens\TextToken;
use hydrogen\view\tokens\VariableToken;
use hydrogen\view\tokens\ArgumentToken;
use hydrogen\view\exceptions\TemplateSyntaxException;

class Lexer {
	const TOKEN_BLOCK = 1;
	const TOKEN_COMMENT = 2;
	const TOKEN_FILTER = 3;
	const TOKEN_TEXT = 4;
	const TOKEN_VARIABLE = 5;
	const TOKEN_ARGUMENT = 6;
	
	const BLOCK_OPENTAG = "{%";
	const BLOCK_CLOSETAG = "%}";
	const BLOCK_COMMAND_ARG_SEPARATOR = " ";
	const COMMENT_OPENTAG = "{#";
	const COMMENT_CLOSETAG = "#}";
	const VARIABLE_OPENTAG = "{{";
	const VARIABLE_CLOSETAG = "}}";
	const VARIABLE_LEVEL_SEPARATOR = ".";
	const VARIABLE_FILTER_SEPARATOR = "|";
	const VARIABLE_FILTER_ARGUMENT_SEPARATOR = ":";
	
	public static function tokenize($origin, $data) {
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
				$tokens[] = static::getVariableToken($origin,
					trim(substr($line, strlen(self::VARIABLE_OPENTAG),
					strlen($line) - strlen(self::VARIABLE_OPENTAG) -
					strlen(self::VARIABLE_CLOSETAG))));
			}
			// Check for block tag
			else if (static::surroundedBy($line, self::BLOCK_OPENTAG,
				self::BLOCK_CLOSETAG)) {
				$tokens[] = static::getBlockToken($origin,
					trim(substr($line, strlen(self::BLOCK_OPENTAG),
					strlen($line) - strlen(self::BLOCK_OPENTAG) -
					strlen(self::BLOCK_CLOSETAG))));
			}
			// Check for comment tag
			else if (static::surroundedBy($line, self::COMMENT_OPENTAG,
				self::COMMENT_CLOSETAG)) {
				$tokens[] = new CommentToken($origin,
					trim(substr($line, strlen(self::COMMENT_OPENTAG),
					strlen($line) - strlen(self::COMMENT_OPENTAG) -
					strlen(self::COMMENT_CLOSETAG))));
			}
			// It must be text!  But skip it if it's empty.
			else if (($text = trim($line)) !== '')
				$tokens[] = new TextToken($origin, $text);
		}
		return $tokens;
	}
	
	public static function getBlockToken($origin, $data) {
		$split = explode(self::BLOCK_COMMAND_ARG_SEPARATOR, $data, 2);
		if (!$split)
			throw new TemplateSyntaxException("Empty block tag in $origin");
		return new BlockToken($origin, $data, $split[0],
			isset($split[1]) ? $split[1] : false);
	}
	
	public static function getVariableToken($origin, $data) {
		$tokens = static::quoteSafeExplode($data,
			self::VARIABLE_FILTER_SEPARATOR);
		$varStr = array_shift($tokens);
		$drillDowns = explode(self::VARIABLE_LEVEL_SEPARATOR, $varStr);
		$var = array_shift($drillDowns);
		$filters = array();
		foreach ($tokens as $token) {
			$fArgs = static::quoteSafeExplode($token,
				self::VARIABLE_FILTER_ARGUMENT_SEPARATOR);
			$filter = array_shift($fArgs);
			for ($i = 0; $i < count($fArgs); $i++) {
				if (static::surroundedby($fArgs[$i], '"', '"')) {
					$fArgs[$i] = stripslashes($fArgs[$i]);
					$fArgs[$i] = new ArgumentToken($origin, $fArgs[$i],
						ArgumentToken::ARG_NATIVE, substr($fArgs[$i], 1, -1));
				}
				else {
					$fArgs[$i] = new ArgumentToken($origin, $fArgs[$i],
						ArgumentToken::ARG_VARIABLE,
						static::getVariableToken($origin, $fArgs[$i]));
				}
			}
			$filters[] = new FilterToken($origin, $token, $filter, $fArgs);
		}
		return new VariableToken($origin, $data, $var, $drillDowns, $filters);
	}
	
	public static function quoteSafeExplode($str, $delim,
			$enclosure='"', $esc='\\', $limit=false) {
		$exploded = array();
		$inQuotes = false;
		$escaping = false;
		$lastEscape = false;
		$cursor = 0;
		if ($limit !== false && $limit < 1)
			$limit = 1;
		while (true) {
			// Get the first artefact
			$dPos = strpos($str, $delim, $cursor);
			$qPos = strpos($str, $enclosure, $cursor);
			$ePos = strpos($str, $esc, $cursor);
			$cursor = static::minIgnoreFalse(array($dPos, $qPos, $ePos));
			
			// Are we done?
			if ($cursor === false ||
					($limit !== false && count($exploded) == $limit - 1)) {
				$exploded[] = $str;
				break;
			}
			
			// Should we kill the escape?
			if ($escaping && $lastEscape !== false && $lastEscape < $cursor - 1)
				$escaping = false;
			
			// Start the state machine
			if ($cursor === $dPos) {
				if (!$inQuotes) {
					$exploded[] = substr($str, 0, $cursor);
					$str = substr($str, $cursor + 1);
					$cursor = 0;
				}
				else
					$cursor++;
			}
			else if ($cursor === $qPos) {
				if ($escaping)
					$escaping = false;
				else
					$inQuotes = !$inQuotes;
				$cursor++;
			}
			else if ($cursor === $ePos) {
				$escaping = !$escaping;
				if ($escaping)
					$lastEscape = $cursor;
				$cursor++;
			}
		}
		return $exploded;
	}
	
	protected static function minIgnoreFalse($nums) {
		$min = false;
		foreach ($nums as $num) {
			if ($num !== false && ($min === false || $num < $min))
				$min = $num;
		}
		return $min;
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