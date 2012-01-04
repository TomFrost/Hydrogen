<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen;

/**
 * The Node interface defines a class that is capable of generating raw PHP
 * template output, to be added to an instance of
 * {@link \hydrogen\view\engines\hydrogen\PHPFile} so that it can be
 * compiled into a full PHP page.
 *
 * Each Node object defines one small part of page output.  When many nodes
 * inject their output to the PHPFile instance in order, a full PHP template
 * page is created.
 *
 * @see \hydrogen\view\engines\hydrogen\Tag
 */
interface Node {
	
	/**
	 * Instructs the node to submit its output and any other applicable PHP
	 * code to an instance of {@link \hydrogen\view\engines\hydrogen\PHPFile}.
	 * No return data is necessary.
	 *
	 * @param \hydrogen\view\engines\hydrogen\PHPFile $phpFile The instance of
	 * 		PHPFile to which all necessary page body content, functions, and
	 * 		variable declarations are added.
	 */
	public function render($phpFile);
}

?>