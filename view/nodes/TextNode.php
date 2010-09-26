<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\nodes;

use hydrogen\view\Node;

class TextNode implements Node {
	protected $text;
	
	public function __construct($text) {
		$this->text = $text;
	}
	
	public function render($context) {
		echo $this->text;
	}
}