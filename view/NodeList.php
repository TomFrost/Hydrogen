<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

class NodeList {
	protected $nodes;
	
	public function __construct() {
		$this->nodes = array();
	}
	
	public function addNode($node) {
		$this->nodes[] = $node;
	}
	
	public function render() {
		$rendered = '';
		foreach ($nodes as $node)
			$rendered .= $node->render();
		return $rendered;
	}
}

?>