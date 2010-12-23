<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\nodes;

use hydrogen\view\engines\hydrogen\Node;
use hydrogen\view\engines\hydrogen\exceptions\NoSuchFilterException;
use hydrogen\view\engines\hydrogen\PHPFile;

class FilterNode implements Node {
	protected $nodes;
	protected $filters;
	protected $origin;

	public function __construct($nodes, $filters, $origin) {
		$this->nodes = $nodes;
		$this->filters = $filters;
		$this->origin = $origin;
	}

	public function render($phpFile) {
		$content = $this->nodes->render();
		$var = '$temp';
		foreach ($this->filters as $filter) {
			$class = '\hydrogen\view\engines\hydrogen\filters\\' .
				ucfirst(strtolower($filter->filter)) . 'Filter';
			if (!@class_exists($class)) {
				throw new NoSuchFilterException('Filter in "' .
					$this->origin . '" does not exist: "' .
					$filter->filter . '".');
			}
			$var = $class::applyTo($var, $filter->args, $phpFile);
		}
		$phpFile->addPageContent(PHPFile::PHP_OPENTAG . 'ob_start();' .
			PHPFile::PHP_CLOSETAG . $content . PHPFile::PHP_OPENTAG .
			'$temp = ob_get_contents(); ob_end_clean(); echo ' .
			$var . ';' . PHPFile::PHP_CLOSETAG);
	}
}

?>