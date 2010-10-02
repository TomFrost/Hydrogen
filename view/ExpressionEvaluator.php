<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

use hydrogen\view\exceptions\NoSuchVariableException;
use hydrogen\view\exceptions\NoSuchFilterException;

class ExpressionEvaluator {

	const STATE_NONE = 0;
	const STATE_OP = 1;
	const STATE_NUM = 2;
	const STATE_VAR = 3;

	protected static $operators = array('-', '+', '/', '*', '%', '(', ')',
		'<', '>', '==', '<=', '>=', '&&', '||');

	protected static $translations = array(
		"and" => "&&",
		"or" => "||",
		"=" => "=="
	);

	// This is a statically accessed class
	protected function __construct() {}

	public static function evaluate($expr, $context) {
		$php = static::exprToPHP($expr);
		return eval("return $php;");
	}

	public static function exprToPHP(&$expr) {
		$state = self::STATE_NONE;
		$token = '';
		$php = '';
		$len = strlen($expr);
		for ($i = 0; $i < $len; $i++) {
			$php .= $expr[$i];
		}
		return $php;
	}

	public static function evalVariableTokens($variable, $drilldowns, $filters,
			$context) {
		$var = $context->get($variable);
		$level = 0;
		foreach ($drilldowns as $dd) {
			if (isset($var[$dd]))
				$var = $var[$dd];
			else if (isset($var->$dd))
				$var = $var->$dd;
			else if (is_object($var)) {
				$methods = get_class_methods($var);
				if (in_array(($func = "get" . ucfirst($dd)), $methods) ||
						in_array(($func = "is" . ucfirst($dd)), $methods) ||
						in_array(($func = "get_" . $dd), $methods) ||
						in_array(($func = "is_" . $dd), $methods))
					$var = call_user_func(array($var, $func));
			}
			else {
				$varName = $this->variable;
				for ($i = 0; $i <= $level; $i++)
					$varName .= '.' . $this->drilldowns[$i];
				$e = new NoSuchVariableException("Variable does not exist in context: $varName");
				$e->variable = $varName;
				throw $e;
			}
			$level++;
		}
		foreach ($filters as $filter) {
			$class = '\hydrogen\view\filters\\' .
				ucfirst(strtolower($filter->filter)) . 'Filter';
			if (!@class_exists($class)) {
				$e = new NoSuchFilterException('Filter does not exist: "' .
					$filter . '".');
				$e->filter = $filter;
				throw $e;
			}
			$var = $class::applyTo($var, $filter->args);
		}
		echo $var;
	}

	public static function evalVariableString($varString, $context) {
		$token = Lexer::getVariableToken("expr", $varString);
		return static::evalVariableTokens($token->variable, $token->drilldowns,
			$token->filters);
	}
}

?>