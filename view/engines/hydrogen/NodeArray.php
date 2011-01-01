<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen;

use hydrogen\view\engines\hydrogen\PHPFile;

class NodeArray extends \ArrayObject {

	public function render($phpFile=false) {
		$phpFile = $phpFile ?: new PHPFile();
		foreach ($this as $node)
			$node->render($phpFile);
		return $phpFile->getPHP();
	}
}

?>