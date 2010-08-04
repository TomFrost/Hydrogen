<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\loaders;

use hydrogen\view\View;
use hydrogen\view\Loader;
use hydrogen\view\exceptions\NoSuchViewException;

class FileLoader implements Loader {
	
	public function load($viewName) {
		$path = View::getViewPath($viewName);
		$page = file_get_contents($path);
		if ($page === false) {
			throw new NoSuchViewException("View " . $path .
				" does not exist.");
		}
		return $page;
	}
}

?>