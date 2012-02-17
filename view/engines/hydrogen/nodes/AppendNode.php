<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\nodes;

use hydrogen\view\engines\hydrogen\Node;
use hydrogen\view\engines\hydrogen\PHPFile;
use hydrogen\config\Config;

class AppendNode implements Node {
	protected $variable;

	public function __construct($variable) {
		$this->variable = &$variable;
	}

	public function render($phpFile) {
		$phpFile->addPageContent(file_get_contents($this->variable));
	}
}

?>