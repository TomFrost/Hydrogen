<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\nodes;

use hydrogen\view\Node;

class VariableNode implements Node {
	protected $variable;
	protected $drilldowns;
	protected $filters;
	protected $origin;
	
	public function __construct($variable, $drilldowns, $filters, $origin) {
		$this->variable = $variable;
		$this->drilldowns = $drilldowns;
		$this->filters = $filters;
		$this->origin = $origin;
	}
	
	public function render($context) {
		echo $this->variable;
	}
}