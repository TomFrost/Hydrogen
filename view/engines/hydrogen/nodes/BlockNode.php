<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\nodes;

use hydrogen\view\engines\hydrogen\Node;
use hydrogen\view\engines\hydrogen\nodes\BlockNode;

class BlockNode implements Node {
	protected $nodes;

	public function __construct($nodes) {
		$this->nodes = $nodes;
	}
	
	public function __clone() {
		return new BlockNode($this->nodes);
	}

	public function setNodes($nodes) {
		$this->nodes = $nodes;
	}

	public function render($phpFile) {
		$this->nodes->render($phpFile);
	}
}