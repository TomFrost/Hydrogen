<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\nodes;

use hydrogen\view\engines\hydrogen\Node;
use hydrogen\view\engines\hydrogen\PHPFile;

class SetNode implements Node {
	protected $variable;
	protected $toNative;
	protected $toVar;
	protected $toNodes;

	public function __construct($variable, $toNative, $toVar, $toNodes) {
		$this->variable = $variable;
		$this->toNative = $toNative;
		$this->toVar = $toVar;
		$this->toNodes = $toNodes;
	}

	public function render($phpFile) {
		$phpFile->addPageContent(PHPFile::PHP_OPENTAG);
		$setVar = $this->variable->getVariablePHP($phpFile, true);
		if ($this->toNative !== false)
			$phpFile->addPageContent($setVar . ' = ' . $this->toNative . ';');
		else if ($this->toVar !== false) {
			$phpFile->addPageContent($setVar . ' = ' .
				$this->toVar->getVariablePHP($phpFile) . ';');
		}
		else {
			$phpFile->addPageContent('ob_start();' . PHPFile::PHP_CLOSETAG);
			$this->toNodes->render($phpFile);
			$phpFile->addPageContent(PHPFile::PHP_OPENTAG . $setVar .
				' = trim(ob_get_contents()); ob_end_clean();');
		}
		$phpFile->addPageContent(PHPFile::PHP_CLOSETAG);
	}
}

?>