<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\nodes;

use hydrogen\view\engines\hydrogen\ExpressionParser;
use hydrogen\view\engines\hydrogen\Node;
use hydrogen\view\engines\hydrogen\exceptions\NoSuchFilterException;
use hydrogen\view\engines\hydrogen\exceptions\NoSuchVariableException;
use hydrogen\view\engines\hydrogen\Lexer;
use hydrogen\view\engines\hydrogen\PHPFile;
use hydrogen\config\Config;

class VariableNode implements Node {
	protected $varLevels;
	protected $filters;
	protected $origin;

	public function __construct($varLevels, $filters, $origin) {
		$this->varLevels = $varLevels;
		$this->filters = $filters ?: array();
		$this->origin = $origin;
	}

	public function render($phpFile) {
		$phpFile->addPageContent(PHPFile::PHP_OPENTAG);
		$printVar = Config::getVal('view', 'print_missing_var') ?: false;
		if ($printVar)
			$phpFile->addPageContent('try {');
		$phpFile->addPageContent('echo ' . $this->getVariablePHP($phpFile) .
			';');
		if ($printVar)
			$phpFile->addPageContent('} catch (\hydrogen\view\exceptions\NoSuchVariableException $e) { echo "{?", $e->variable, "?}"; }');
		$phpFile->addPageContent(PHPFile::PHP_CLOSETAG);
	}
	
	public function getVariablePHP($phpFile) {
		$var = '$context';
		foreach ($this->varLevels as $level)
			$var .= "->" . $level;
		$var .= "->getValue()";
		foreach ($this->filters as $filter) {
			$class = '\hydrogen\view\engines\hydrogen\filters\\' .
				ucfirst(strtolower($filter->filter)) . 'Filter';
			if (!@class_exists($class)) {
				throw new NoSuchFilterException('Filter in "' .
					$this->origin . '" does not exist: "' .
					$filter . '".');
			}
			$var = $class::applyTo($var, $filter->args, $phpFile);
		}
		return $var;
	}
}