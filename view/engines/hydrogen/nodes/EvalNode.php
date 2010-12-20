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
	protected $origin;

	public function __construct($expr, $origin) {
		$this->expr = $expr;
	}

	public function render($phpFile) {
		$result = ExpressionParser::exprToPHP($this->expr, $phpFile,
			$this->origin);
		$phpFile->addPageContent(PHPFile::PHP_OPENTAG .
			'if (is_bool($temp = (' . $result .
			'))) echo $temp ? "true" : "false"; else echo $temp;' .
			PHPFile::PHP_CLOSETAG);
	}
}