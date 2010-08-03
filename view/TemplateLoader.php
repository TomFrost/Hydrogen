<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

use hydrogen\view\exceptions\NoSuchViewException;

/**
 * TemplateLoader is responsible for the reading, parsing, caching, and
 * rendering of Hdyrogen templates.  Heavily modeled after Django.
 */
class TemplateLoader {
	private $templatePath;
	
	/**
	 * Creates a new template loader for the given template.
	 *
	 * @param string templatePath The absolute path to the template to be
	 * 		loaded.
	 */
	public function __construct($templatePath) {
		$this->templatePath = $templatePath;
	}
	
	/**
	 * Show the rendered view, caching it or reading it from the cache if
	 * appropriate.
	 */
	public function display() {
		
	}
}

?>