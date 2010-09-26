<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

use hydrogen\view\exceptions\NoSuchViewException;
use hydrogen\view\LoaderFactory;

class Template {
	protected $loader;
	protected $viewName;
	protected $views;
	
	/**
	 * Creates a new template object for the given template name.
	 *
	 * @param string templatePath The absolute path to the template to be
	 * 		loaded.
	 */
	public function __construct($viewName, $loader=false) {
		$this->viewName = $viewName;
		$this->views = array();
		$this->loader = $loader ?: LoaderFactory::getLoader();
	}
	
	/**
	 * Shows the rendered view, caching it or reading it from the cache if
	 * appropriate.
	 *
	 * @param ContextStack context The appropriate context to use for rendering
	 * 		this template.
	 */
	public function render($context) {
		if (!$this->displayCached()) {
			$parser = new Parser($this->viewName, $this->loader);
			$nodes = $parser->parse();
			echo $nodes->render();
		}
	}
	
	/**
	 * Attempts to display the cached version of this view.
	 *
	 * @return boolean true if the page was displayed; false if it needs to be
	 * 		re-parsed/re-cached.
	 */
	protected function displayCached() {
		return false;
	}
}

?>