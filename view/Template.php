<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

use hydrogen\view\exceptions\NoSuchViewException;
use hydrogen\view\LoaderFactory;

class Template {
	protected $viewName;
	protected $views;
	
	/**
	 * Creates a new template object for the given template name.
	 *
	 * @param string templatePath The absolute path to the template to be
	 * 		loaded.
	 */
	public function __construct($viewName) {
		$this->viewName = $viewName;
		$this->views = array();
	}
	
	/**
	 * Shows the rendered view, caching it or reading it from the cache if
	 * appropriate.
	 */
	public function render() {
		if (!$this->displayCached()) {
			$loader = LoaderFactory::getLoader();
			$page = $loader->load($this->viewName);
			echo $page;
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