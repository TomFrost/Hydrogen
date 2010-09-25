<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

class NodeArray extends \ArrayObject {
	public function render() {
		$rendered = '';
		foreach ($this as $node)
			$rendered .= $node->render();
		return $rendered;
	}
}

?>