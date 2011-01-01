<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

use hydrogen\config\Config;

class LoaderFactory {
	protected static $loaders = array();
	
	public static function getLoader($loaderType=false) {
		if (!$loaderType)
			$loaderType = Config::getVal("view", "loader") ?: "File";
		if (!isset(static::$loaders[$loaderType])) {
			$class = __NAMESPACE__ . '\loaders\\' . $loaderType . 'Loader';
			static::$loaders[$loaderType] = new $class();
		}
		return static::$loaders[$loaderType];
	}
}

?>