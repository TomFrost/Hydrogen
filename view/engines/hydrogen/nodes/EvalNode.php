<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\nodes;

use hydrogen\view\engines\hydrogen\Node;
use hydrogen\view\engines\hydrogen\ExpressionParser;
use hydrogen\view\engines\hydrogen\PHPFile;

class EvalNode implements Node {
	protected $expr;

	public function __construct($expr) {
		$this->expr = $expr;
	}

	public function render($phpFile) {
		$result = ExpressionParser::exprToPHP($this->expr);
		$phpFile->addPageContent(PHPFile::PHP_OPENTAG .
			'echo ' . $result . ';' .
			PHPFile::PHP_CLOSETAG);
	}
}