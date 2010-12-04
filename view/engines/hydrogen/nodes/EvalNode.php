<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\nodes;

use hydrogen\view\engines\hydrogen\Node;
use hydrogen\view\engines\hydrogen\ExpressionEvaluator;

class EvalNode implements Node {
	protected $expr;

	public function __construct($expr) {
		$this->expr = $expr;
	}

	public function render($context) {
		$result = ExpressionEvaluator::evaluate($this->expr, $context);
		if (is_bool($result))
			echo $result ? 'true' : 'false';
		else
			echo $result;
	}
}