<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\nodes;

use hydrogen\view\engines\hydrogen\Node;
use hydrogen\view\engines\hydrogen\ExpressionParser;
use hydrogen\view\engines\hydrogen\PHPFile;

class ForNode implements Node {
	protected $keyVar;
	protected $valVar;
	protected $arrayVar;
	protected $forNodes;
	protected $emptyNodes;

	public function __construct($keyVar, $valVar, $arrayVar, $forNodes,
			$emptyNodes) {
		$this->keyVar = &$keyVar;
		$this->valVar = &$valVar;
		$this->arrayVar = &$arrayVar;
		$this->forNodes = &$forNodes;
		$this->emptyNodes = &$emptyNodes;
	}

	public function render($phpFile) {
		$arrayPHP = $this->arrayVar->getVariablePHP($phpFile);
		$phpFile->addPageContent(PHPFile::PHP_OPENTAG . '$array = ' .
				$arrayPHP . '; ');
		if ($this->emptyNodes) {
			$phpFile->addPageContent('if (empty($array)) { ' .
				PHPFile::PHP_CLOSETAG);
			$this->emptyNodes->render($phpFile);
			$phpFile->addPageContent(PHPFile::PHP_OPENTAG . '} else { ');
		}
		$phpFile->addPageContent('foreach ($array as ');
		if ($this->keyVar)
			$phpFile->addPageContent('$key => ');
		$phpFile->addPageContent('$value) { $context->push(); ');
		if ($this->keyVar) {
			$phpFile->addPageContent(
				$this->keyVar->getVariablePHP($phpFile, true) . ' = $key; ');
		}
		$phpFile->addPageContent(
			$this->valVar->getVariablePHP($phpFile, true) . ' = $value; ' .
				PHPFile::PHP_CLOSETAG);
		$this->forNodes->render($phpFile);
		$phpFile->addPageContent(PHPFile::PHP_OPENTAG . ' $context->pop(); }');
		if ($this->emptyNodes)
			$phpFile->addPageContent(' }');
		$phpFile->addPageContent(PHPFile::PHP_CLOSETAG);
	}
}

?>