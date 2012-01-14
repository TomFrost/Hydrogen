<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\nodes;

use hydrogen\view\engines\hydrogen\Node;
use hydrogen\view\engines\hydrogen\PHPFile;

class IncludeNode implements Node {
	protected $variable;

	public function __construct($variable) {
		$this->variable = &$variable;
	}

	public function render($phpFile) {
		$phpFile->addPageContent(PHPFile::PHP_OPENTAG . '$this->loadView(' .
			$this->variable->getVariablePHP($phpFile) . ');' .
			PHPFile::PHP_CLOSETAG);
	}
}

?>