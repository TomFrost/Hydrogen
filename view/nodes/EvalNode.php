<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\nodes;

use hydrogen\view\Node;
use hydrogen\view\ExpressionEvaluator;

class EvalNode implements Node {
	protected $expr;

	public function __construct($expr) {
		$this->expr = $expr;
	}

	public function render($context) {
		echo ExpressionEvaluator::evaluate($this->expr, $context);
	}
}