<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen;

use hydrogen\view\engines\hydrogen\PHPFile;

class NodeArray extends \ArrayObject {

	public function render() {
		$phpFile = new PHPFile();
		foreach ($this as $node)
			$node->render($phpFile);
		return $phpFile->getPHP();
	}
}

?>