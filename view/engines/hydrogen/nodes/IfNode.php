<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\nodes;

use hydrogen\view\engines\hydrogen\Node;
use hydrogen\view\engines\hydrogen\ExpressionParser;
use hydrogen\view\engines\hydrogen\PHPFile;

class IfNode implements Node {
	protected $condChain;
	protected $origin;

	public function __construct($condChain, $origin) {
		$this->condChain = $condChain;
		$this->origin = $origin;
	}

	public function render($phpFile) {
		$first = true;
		$phpFile->addPageContent(PHPFile::PHP_OPENTAG);
		foreach ($this->condChain as $cond) {
			if ($first) {
				$phpFile->addPageContent('if (' .
					ExpressionParser::exprToPHP($cond[0], $phpFile,
					$this->origin) . ') {');
				$first = false;
			}
			else if ($cond[0] !== true) {
				$phpFile->addPageContent('else if (' .
					ExpressionParser::exprToPHP($cond[0], $phpFile,
					$this->origin) . ') {');
			}
			else
				$phpFile->addPageContent('else {');
			$phpFile->addPageContent(PHPFile::PHP_CLOSETAG);
			$cond[1]->render($phpFile);
			$phpFile->addPageContent(PHPFile::PHP_OPENTAG . '} ');
		}
		$phpFile->addPageContent(PHPFile::PHP_CLOSETAG);
	}
}

?>