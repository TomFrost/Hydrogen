<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen;

use hydrogen\view\engines\hydrogen\FilterArgument;
use hydrogen\view\engines\hydrogen\Token;
use hydrogen\view\engines\hydrogen\tokens\BlockToken;
use hydrogen\view\engines\hydrogen\tokens\CommentToken;
use hydrogen\view\engines\hydrogen\tokens\FilterToken;
use hydrogen\view\engines\hydrogen\tokens\TextToken;
use hydrogen\view\engines\hydrogen\tokens\VariableToken;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

/**
 * The Hydrogen Templating Engine Lexer is responsible for knowing the core
 * syntax of the templating language, and breaking a template into logical
 * tokens that the {@link \hydrogen\view\engines\hydrogen\Parser} can
 * iterate through.
 *
 * The Lexer also exposes methods that can be useful for tag, node, and filter
 * extensions to the templating languages, allowing them the ability to use
 * quote-safe explosion, check strings for surrounding characters, and
 * lexically parse variable strings just like the Lexer does.
 */
class Lexer {
	const TOKEN_BLOCK = 1;
	const TOKEN_COMMENT = 2;
	const TOKEN_FILTER = 3;
	const TOKEN_TEXT = 4;
	const TOKEN_VARIABLE = 5;

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

	/**
	 * Turns a given template string into an array of tokens, each defining a
	 * small piece of the template that can be independently parsed.
	 *
	 * @param string $origin The name of the template whose data is to be
	 * 		tokenized.
	 * @param string $data The fully loaded template to be tokenized.
	 * @return array An array of {@link Token} objects representing this
	 * 		template.
	 * @throws TemplateSyntaxException when any illegal template syntax is
	 * 		found that cannot be parsed.
	 */
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
			else
				$tokens[] = new TextToken($origin, $line);
		}
		return $tokens;
	}

	/**
	 * Creates a {@link BlockToken} from the contents of a block tag.
	 *
	 * @param string $origin The template in which the block tag was found
	 * @param string $data The contents of the block tag, without the
	 * 		surrounding opening and closing tags
	 * @return BlockToken A BlockToken created from the provided tag contents.
	 * @throws TemplateSyntaxException if the block tag contents were illegally
	 * 		formatted.
	 */
	public static function getBlockToken($origin, $data) {
		$split = explode(self::BLOCK_COMMAND_ARG_SEPARATOR, $data, 2);
		if (!$split)
			throw new TemplateSyntaxException("Empty block tag in $origin");
		return new BlockToken($origin, $data, $split[0],
			isset($split[1]) ? $split[1] : false);
	}

	/**
	 * Creates a {@link VariableToken} from the contents of a variable tag.
	 *
	 * @param string $origin The template in which the variable tag was found
	 * @param string $data The contents of the variable tag, without the
	 * 		surrounding opening and closing tags
	 * @return VariableToken A VariableToken created from the provided tag
	 * 		contents.
	 * @throws TemplateSyntaxException if the variable tag contents were
	 * 		illegally formatted.
	 */
	public static function getVariableToken($origin, $data) {
		$tokens = static::quoteSafeExplode($data,
			self::VARIABLE_FILTER_SEPARATOR);
		$varStr = array_shift($tokens);
		$varLevels = explode(self::VARIABLE_LEVEL_SEPARATOR, $varStr);
		foreach ($varLevels as $level) {
			if (!preg_match('/^[a-zA-Z]([a-zA-Z0-9_]+)?$/', $level)) {
				throw new TemplateSyntaxException('Variable "' . $level .
					'" in template "' . $origin .
					'" contains illegal characters.');
			}
		}
		$filters = array();
		foreach ($tokens as $token) {
			$fArgs = static::quoteSafeExplode($token,
				self::VARIABLE_FILTER_ARGUMENT_SEPARATOR);
			$filter = array_shift($fArgs);
			$numArgs = count($fArgs);
			for ($i = 0; $i < $numArgs; $i++) {
				if (static::surroundedby($fArgs[$i], '"', '"')) {
					$fArgs[$i] = stripslashes($fArgs[$i]);
					$fArgs[$i] = new FilterArgument(
						substr($fArgs[$i], 1, -1)
					);
				}
				else if (is_numeric($fArgs[$i])) {
					$fArgs[$i] = new FilterArgument(
						ctype_digit($fArgs[$i]) ? (int)$fArgs[$i] :
							(float)$fArgs[$i]
					);
				}
				else if (($bool = strtolower($fArgs[$i]) === 'true') ||
						$bool === 'false') {
					$fArgs[$i] = new FilterArgument(
						$fArgs[$i] === 'true' ? true : false
					);
				}
				else if (ctype_alpha($fArgs[$i][0])) {
					$fArgs[$i] = new FilterArgument(
						$fArgs[$i] = static::getVariableToken($origin,
							$fArgs[$i])
					);
				}
				else
					throw new TemplateSyntaxException(
						"Illegal filter argument in $origin: " . $fArgs[$i]);
			}
			$filters[] = new FilterToken($origin, $token, $filter, $fArgs);
		}
		return new VariableToken($origin, $data, $varLevels, $filters);
	}

	/**
	 * Breaks a string into an array based on delimiters found in the string,
	 * but ignores delimiters that fall between quote characters (or another
	 * defined character).  This is an extension of PHP's native
	 * {@link http://php.net/manual/en/function.explode.php explode()}
	 * function.
	 *
	 * @param string $str The string to be broken into an array.
	 * @param string $delim The delimiter at which pieces of the string should
	 * 		be split
	 * @param string $enclosure The character (usually a single or double
	 * 		quote) which defines a boundary in which the delimiter should be
	 * 		ignored.  If not specified, a double-quote (") is used by default.
	 * @param string $esc The escape character to allow the enclosure character
	 * 		to be used within an enclosure boundary.  If not specified, a
	 * 		backslash (\) is used by default.
	 * @param int|boolean $limit The maximum number of elements to break the
	 * 		string into.  If this limit is reached, the last element of the
	 * 		returned array will contain the remainder of the string.  If
	 * 		false, there will be no limit to the array size.  Default is false.
	 * @return array An array of strings broken at the specified delimiter,
	 * 		with the delimiter removed (with the exception of those in an
	 * 		enclosure).
	 */
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

	/**
	 * Finds the minimum number in an array of numbers, ignoring any array
	 * elements that are boolean false.
	 *
	 * @param array $nums An array of numbers and, potentially, boolean false
	 * 		values.
	 * @return int|float|boolean The minimum integer or float found in the
	 * 		array, or false if no numeric values were found in the array.
	 */
	protected static function minIgnoreFalse($nums) {
		$min = false;
		foreach ($nums as $num) {
			if ($num !== false && ($min === false || $num < $min))
				$min = $num;
		}
		return $min;
	}

	/**
	 * Checks to see if the provided string is surrounded by the given starting
	 * and ending strings.
	 *
	 * @param string $haystack The string to check for surrounding characters
	 * @param string $startsWith The character(s) to check for at the beginning
	 * 		of the string
	 * @param string $endsWith The character(s) to check for at the end of the
	 * 		string
	 * @return boolean true if the string begins with the character(s) defined
	 * 		in $startsWith and ends with the character(s) defined in $endsWith,
	 * 		false otherwise.
	 */
	public static function surroundedBy($haystack, $startsWith, $endsWith) {
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