<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

use hydrogen\config\Config;

/**
 * LoaderFactory is a statically-accessed class that manages the creation of
 * {@link \hydrogen\view\Loader} objects.  It allows one Loader of each type to
 * be created, but should a type be requested multiple times, the original
 * instance will be returned each time.
 */
class LoaderFactory {
	protected static $loaders = array();
	
	/**
	 * LoaderFactory should never be instantiated.
	 */
	protected static function __construct() {}
	
	/**
	 * Gets an instance (newly created or old) of a Loader.
	 *
	 * @param string $loaderType The type of loader (loader name, with a
	 * 		capital first letter) of which to get an instance.  This argument
	 * 		is optional; if false, the loader type specified in the
	 * 		[view]=>loader config value will be used.  This value is normally
	 * 		specified within hydrogen.autoload.php.
	 * @return \hydrogen\view\Loader a Loader of the appropriate type.
	 */
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