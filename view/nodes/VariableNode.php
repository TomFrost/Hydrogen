<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\nodes;

use hydrogen\view\ExpressionEvaluator;
use hydrogen\view\Node;
use hydrogen\view\exceptions\NoSuchFilterException;
use hydrogen\view\exceptions\NoSuchVariableException;
use hydrogen\config\Config;

class VariableNode implements Node {
	protected $variable;
	protected $drilldowns;
	protected $filters;
	protected $origin;

	public function __construct($variable, $drilldowns, $filters, $origin) {
		$this->variable = $variable;
		$this->drilldowns = $drilldowns ?: array();
		$this->filters = $filters ?: array();
		$this->origin = $origin;
	}

	public function render($context) {
		try {
			$val = ExpressionEvaluator::evalVariableTokens($this->variable,
				$this->drilldowns, $this->filters, $context);
		}
		catch (NoSuchVariableException $e) {
			$this->reportMissing($e->variable);
			return;
		}
		catch (NoSuchFilterException $e) {
			$fe = new NoSuchFilterException('Filter "' . $e->filter .
				'" does not exist in template "' . $this->origin . '".');
			$fe->filter = &$e->filter;
			throw $fe;
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