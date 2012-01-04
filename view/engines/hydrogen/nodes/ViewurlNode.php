<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\nodes;

use hydrogen\view\engines\hydrogen\Node;
use hydrogen\view\engines\hydrogen\PHPFile;

class ViewurlNode implements Node {
	protected $path;

	public function __construct($path) {
		$this->path = $path;
	}

	public function render($phpFile) {
		$phpFile->addPageContent(PHPFile::PHP_OPENTAG .
			'echo $this->viewURL(\'' . $this->path . '\');' .
			PHPFile::PHP_CLOSETAG);
	}
}

?>