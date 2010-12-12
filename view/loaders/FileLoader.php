<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\loaders;

use hydrogen\config\Config;
use hydrogen\view\View;
use hydrogen\view\Loader;
use hydrogen\view\exceptions\NoSuchViewException;

class FileLoader implements Loader {
	
	/**
	 * Translates a view name into an absolute path at which the view can
	 * be found.
	 *
	 * @param string viewName The name of the view to be found.
	 * @return string The absolute path to the requested view.
	 */
	public function getViewPath($viewName) {
		$path = Config::getRequiredVal("view", "folder") .
			DIRECTORY_SEPARATOR . $viewName .
			Config::getRequiredVal("view", "file_extension");
		return Config::getAbsolutePath($path);
	}
	
	public function load($viewName) {
		$path = $this->getViewPath($viewName);
		$page = file_get_contents($path);
		if ($page === false) {
			throw new NoSuchViewException("View " . $path .
				" does not exist.");
		}
		return $page;
	}
}

?>