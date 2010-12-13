<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\nodes;

use hydrogen\view\engines\hydrogen\Node;

class TextNode implements Node {
	protected $text;

	public function __construct($text) {
		$this->text = $text;
	}

	public function render($phpFile) {
		$phpFile->addPageContent($this->text);
	}
}