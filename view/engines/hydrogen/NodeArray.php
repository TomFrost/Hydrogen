<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen;

use hydrogen\view\engines\hydrogen\PHPFile;

/**
 * NodeArray is an extension of PHP's native array() object, giving it a
 * function capable of calling each of its contained Nodes' render() functions
 * in succession.  The result is a single array object capable of generating
 * the PHP for all of its contained
 * {@link \hydrogen\view\engines\hydrogen\Node} objects with one method call.
 */
class NodeArray extends \ArrayObject {

	/**
	 * Generates the PHP code for each of the contained Nodes, calling
	 * {@link \hydrogen\view\engines\hydrogen\Node::render()} on each one
	 * in order and passing it an instance of
	 * {@link \hydrogen\view\engines\hydrogen\PHPFile}.
	 *
	 * @param \hydrogen\view\engines\hydrogen\PHPFile $phpfile The instance
	 * 		of PHPFile that each Node in the NodeArray should use to output
	 * 		its PHP code.  If not specified, a new PHPFile will be
	 * 		automatically created.
	 * @return string the raw PHP code generated from each of the contained
	 * 		Nodes.
	 */
	public function render($phpFile=false) {
		$phpFile = $phpFile ?: new PHPFile();
		foreach ($this as $node)
			$node->render($phpFile);
		return $phpFile->getPHP();
	}
}

?>