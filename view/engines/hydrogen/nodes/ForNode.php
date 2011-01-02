<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
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
		$phpFile->addPrivateDeclaration('forStackIdx', 'array()');
		$phpFile->addPrivateDeclaration('forStackStat', 'array()');
		$arrayPHP = $this->arrayVar->getVariablePHP($phpFile);
		$phpFile->addPageContent(PHPFile::PHP_OPENTAG . '$array = ' .
				$arrayPHP . '; ');
		if ($this->emptyNodes) {
			$phpFile->addPageContent('if (empty($array)) { ' .
				PHPFile::PHP_CLOSETAG);
			$this->emptyNodes->render($phpFile);
			$phpFile->addPageContent(PHPFile::PHP_OPENTAG . '} else { ');
		}
		$phpFile->addPageContent('$forStackIdx[] = -1; ' .
			'$forStackStat[] = $context->get(\'forloop\', true); ' .
			'foreach ($array as ');
		if ($this->keyVar)
			$phpFile->addPageContent('$key => ');
		$loopPHP = <<<'PHP'
			$context->push();
			$idx = &$forStackIdx[count($forStackIdx) - 1];
			$par = &$forStackStat[count($forStackStat) - 1];
			$max = count($array);
			$context->set('forloop', array(
				'counter' => ++$idx + 1,
				'counter0' => $idx,
				'revcounter' => $max - $idx,
				'revcounter0' => $max - $idx - 1,
				'first' => $idx === 0,
				'last' => $max === ($idx + 1),
				'parentloop' => $par
			), true);
PHP;
		$phpFile->addPageContent('$value) { ' . $loopPHP);
		if ($this->keyVar) {
			$phpFile->addPageContent(
				$this->keyVar->getVariablePHP($phpFile, true) . ' = $key; ');
		}
		$phpFile->addPageContent(
			$this->valVar->getVariablePHP($phpFile, true) . ' = $value; ' .
				PHPFile::PHP_CLOSETAG);
		$this->forNodes->render($phpFile);
		$phpFile->addPageContent(PHPFile::PHP_OPENTAG .
			' $context->pop(); } array_pop($forStackIdx); ' .
			'$context->set(\'forloop\', array_pop($forStackStat));');
		if ($this->emptyNodes)
			$phpFile->addPageContent(' }');
		$phpFile->addPageContent(PHPFile::PHP_CLOSETAG);
	}
}

?>