<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\nodes;

use hydrogen\view\engines\hydrogen\Node;

class BlockNode implements Node {
	protected $nodes;

	public function __construct($nodes) {
		$this->nodes = $nodes;
	}

	public function setNodes($nodes) {
		$this->nodes = $nodes;
	}

	public function render($context) {
		$this->nodes->render($context);
	}
}