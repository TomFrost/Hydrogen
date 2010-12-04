<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen;

class NodeArray extends \ArrayObject {

	public function render($context) {
		$rendered = '';
		foreach ($this as $node)
			$rendered .= $node->render($context);
		return $rendered;
	}
}

?>