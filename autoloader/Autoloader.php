<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\autoloader;

use hydrogen\config\Config;

/**
 * The Autoloader class extends Hydrogen's class autoloading capabilities to
 * any other namespace within a PHP app.
 */
class Autoloader {
	
	protected static $namespaces = array();
	protected static $registered = false;
	
	/**
	 * Load a specified class from its PHP file.  This function should only
	 * ever be called by the PHP autoloading system, and registered through
	 * the {@link #register} function.
	 *
	 * @param string $class The full namespace/class string of the class
	 * 		to be loaded.
	 * @return boolean true if the class was loaded; false otherwise.
	 */
	protected static function loadClass($class) {
		$class = ltrim($class, '\\');
		$rootNamespace = '';
		if ($firstSlash = strpos($class, '\\'))
			$rootNamespace = substr($class, 0, $firstSlash);
		if (isset(static::$namespaces[$rootNamespace])) {
			$subNamespace = '';
			if ($lastSlash = strrpos($class, '\\')) {
				$fileName = substr($class, $lastSlash + 1);
				if ($firstSlash < $lastSlash) {
					$subNamespace = substr($class, $firstSlash + 1,
						$lastSlash - $firstSlash);
				}
			}
			else
				$fileName = $class;
			$path = static::$namespaces[$rootNamespace][0];
			$path .= DIRECTORY_SEPARATOR;
			$path .= str_replace('\\', DIRECTORY_SEPARATOR, $subNamespace);
			$path .= (static::$namespaces[$rootNamespace][1] ?
				str_replace('_', DIRECTORY_SEPARATOR, $fileName) :
				$fileName) . '.php';
			return !!include($path);
		}
		return false;
	}
	
	/**
	 * Registers this autoloader and makes it active.  This should only
	 * need to be called in hydrogen.inc.php.
	 *
	 * @return boolean true if the autoloader was successfully registered;
	 * 		false if it was not, or if it has already been registered in the
	 * 		past.
	 */
	public static function register() {
		if (static::$registered)
			return false;
		
		static::$registered = spl_autoload_register(
			'\hydrogen\autoloader\Autoloader::loadClass');
		return static::$registered;
	}
	
	/**
	 * Registers a new namespace with the autoloader.  As soon as this
	 * function is called, classes within that namespace (or within
	 * child namespaces within that namespace) will be autoloaded.
	 *
	 * Note that it's expected that the namespace being added will follow the
	 * proper convention for file organization: the folder structure should
	 * match the namespace exactly, so that a class named something like
	 * myapp\models\exceptions\UserNotFoundException would be found in
	 * models/exceptions/UserNotFoundException.php within the root folder
	 * for the 'myapp' namespace.
	 *
	 * The Autoloader also supports replacing underscores in a filename with
	 * directory separators, which is useful for compatibility with old code
	 * written with a Zend-style autoloader.  This feature is optional on a
	 * per-namespace basis.
	 *
	 * The root folder supplied to this function should point to the first
	 * folder of the namespace itself.  For example, if the above PHP file were
	 * located at this path:
	 * [webroot]/lib/myapp/models/exceptions/UserNotFoundException.php
	 * then the namespace would be 'myapp' and the root folder would be
	 * 'lib/myapp'.
	 *
	 * @param string $namespace The root namespace for which classes should be
	 * 		autoloaded.
	 * @param string $rootFolder The folder that the namespace's files reside
	 * 		in.  Relative paths will be evaluated relative to the base path set
	 * 		in the autoconfig.
	 * @param boolean $replaceUnderscores true to replace any underscores in
	 * 		the class name with directory separators when loading; false to
	 * 		treat them as part of the file name.
	 */
	public static function registerNamespace($namespace, $rootFolder,
			$replaceUnderscores=true) {
		$rootFolder = Config::getAbsolutePath($rootFolder);
		$rootFolder = rtrim($rootFolder, DIRECTORY_SEPARATOR);
		static::$namespaces[$namespace] = array($rootFolder,
			$replaceUnderscores);
	}
	
	/**
	 * The Autoloader should not be instantiated.
	 */
	protected function __construct() {}
}

?>