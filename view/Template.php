<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

use hydrogen\config\Config;
use hydrogen\view\exceptions\NoSuchViewException;
use hydrogen\view\components\Page;

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
	public function display() {
		if (!$this->displayCached()) {
			$page = new Page($this->viewName);
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
	
	/**
	 * Parses through the supplied View.
	 */
	protected function parsePage($viewName) {
		$path = View::getViewPath($viewName);
		$page = file_get_contents($path);
		if ($page === false) {
			throw new NoSuchViewException("View " . $path .
				" does not exist.");
		}
		$tagSplit = preg_split('/(\{%.+)\s*%\}/U', $page, -1,
			PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		print_r($tagSplit);
	}
}

?>