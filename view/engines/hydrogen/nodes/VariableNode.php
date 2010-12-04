<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\nodes;

use hydrogen\view\engines\hydrogen\ExpressionEvaluator;
use hydrogen\view\engines\hydrogen\Node;
use hydrogen\view\engines\hydrogen\exceptions\NoSuchFilterException;
use hydrogen\view\engines\hydrogen\exceptions\NoSuchVariableException;
use hydrogen\view\engines\hydrogen\Lexer;
use hydrogen\config\Config;

class VariableNode implements Node {
	protected $varLevels;
	protected $filters;
	protected $origin;

	public function __construct($varLevels, $filters, $origin) {
		$this->varLevels = $varLevels;
		$this->filters = $filters ?: array();
		$this->origin = $origin;
	}

	public function render($context) {
		try {
			$val = ExpressionEvaluator::evalVariableTokens($this->varLevels,
				$this->filters, $context);
		}
		catch (NoSuchFilterException $e) {
			$fe = new NoSuchFilterException('Filter "' . $e->filter .
				'" does not exist in template "' . $this->origin . '".');
			$fe->filter = &$e->filter;
			throw $fe;
		}
		if ($val === NULL) {
			$this->reportMissing(implode(Lexer::VARIABLE_LEVEL_SEPARATOR,
				$this->varLevels));
			return;
		}
		echo $val;
	}

	protected function reportMissing($varString) {
		if (Config::getVal("view", "print_missing_var"))
			echo "?? " . $varString . " ??";
		else
			throw new NoSuchVariableException('Variable "' .
				$varString . '" does not exist in template "' .
				$this->origin . '".');
	}
}