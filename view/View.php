<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

use hydrogen\config\Config;
use hydrogen\view\ContextStack;
use hydrogen\view\LoaderFactory;
use hydrogen\view\exceptions\NoSuchVariableException;
use hydrogen\view\exceptions\NoSuchViewException;
use hydrogen\view\exceptions\ViewCacheException;

class View {
	
	const DEFAULT_ENGINE = "hydrogen";
	
	protected static $defaultContext = false;
	
	/**
	 * Gets a variable's value from the default View context.
	 *
	 * @param string key The key for which to get the value.
	 * @return the value of the key.
	 * @throws NoSuchVariableException If the key does not exist in the
	 * 		default View context.
	 */
	public static function getVar($key) {
		if (!static::$defaultContext)
			static::$defaultContext = new ContextStack();
		return static::$defaultContext->get($key);
	}
	
	/**
	 * Sets a variable or array of variables to the specified value(s)
	 * in the default View context.  Any variables declared with this function
	 * will be available to any view loaded using the {@link #load}
	 * function.
	 *
	 * @param keyOrArray string|array A variable name to set, or an
	 * 		associative array of varName => value pairs.
	 * @param value mixed The value for the specified key.  If keyOrArray
	 * 		is an associative array, this value is not used.
	 */
	public static function setVar($keyOrArray, $value=false) {
		if (!static::$defaultContext)
			static::$defaultContext = new ContextStack();
		if (is_array($keyOrArray))
			static::$defaultContext->setArray($keyOrArray);
		else
			static::$defaultContext->set($keyOrArray, $value);
	}
	
	/**
	 * Loads and displays the specified view inside of a new ViewSandbox
	 * with the default context (unless otherwise specified).
	 *
	 * @param string viewName The name of the view to load.
	 * @param array|ContextStack|boolean context A context in which to load the
	 * 		view, or an associative array of key/value pairs to be added to
	 * 		the default context before loading the view.  This argument
	 * 		is optional -- use boolean false to ignore.
	 */
	public static function load($viewName, $context=false) {
		$viewContext = static::$defaultContext;
		if (is_array($context))
			static::setVar($context);
		else if ($context !== false)
			$viewContext = $context;
		$sandbox = new ViewSandbox($viewContext);
		static::loadIntoSandbox($viewName, $sandbox);
	}
	
	/**
	 * Loads and displays the specified view inside of an existing
	 * ViewSandbox, respecting the [view]->use_cache setting in the autoconfig
	 * to load the specified view from the cache (or write it into the cache
	 * if it does not exist there) if appropriate.
	 *
	 * @param string viewName The name of the view to load.
	 * @param ViewSandbox sandbox The sandbox into which the view should be
	 * 		loaded.
	 */
	public static function loadIntoSandbox($viewName, $sandbox) {
		if (Config::getRequiredVal('view', 'use_cache'))
			static::loadCachedIntoSandbox($viewName, $sandbox);
		else
			$sandbox->loadRawPHP(static::getViewPHP($viewName));
	}
	
	/**
	 * Loads the cached version of a specified view into the given sandbox.
	 * If the specified view is not found in the cache folder, the view is
	 * rendered to PHP and then written into a new cache file, which is then
	 * loaded for this request and future requests.  This function requires
	 * that Config::setCachePath has been called (probably already done in the
	 * autoconfig file).
	 *
	 * @param string viewName The view file to load from the cache, or create
	 * 		in the cache if it is not found.
	 * @param ViewSandbox sandbox The sandbox into which the cached view should
	 * 		be loaded.
	 * @throws NoSuchViewException if the specified view cannot be found.
	 * @throws ViewCacheException if there is any problem creating/writing to
	 * 		cache files.
	 */
	protected static function loadCachedIntoSandbox($viewName, $sandbox) {
		$path = Config::getCachePath() . '/hydrogen/view/' .
			$viewName . '.php';
		try {
			$sandbox->loadPHPFile($path);
		}
		catch (NoSuchViewException $e) {
			$php = static::getViewPHP($viewName);
			$folder = dirname($path);
			// Create the view cache folder if it doesn't exist.
			if (!file_exists($folder)) {
				$success = @mkdir($folder, 0777, true);
				if (!$success) {
					throw new ViewCacheException(
						'Could not create the view cache folder: "' .
						$folder . '". Check your filesystem permissions!');
				}
			}
			// Attempt to open the file for writing
			$fp = @fopen($path, 'w');
			if (!$fp)
				throw new ViewCacheException('Could not create or open the cached view file for writing: ' . $path);
			// Get the lock on the file.  If we can't get the lock, bypass all
			// this and load the raw PHP into the sandbox; another user is
			// writing the cache file.
			if (@flock($fp, LOCK_EX | LOCAL_NB)) {
				$success = @fwrite($fp, $php);
				@fclose($fp);
				if (!$success) {
					throw new ViewCacheException(
						'Could not write to view cache file.');
				}
				$sandbox->loadPHPFile($path);
			}
			else {
				@fclose($fp);
				$sandbox->loadRawPHP($php);
			}
		}
	}
	
	/**
	 * Retrieves the raw PHP code for any specified view, loading it through
	 * the view loader and template engine specified in the Hydrogen
	 * autoconfig file.
	 *
	 * @param string viewName The name of the view for which the raw PHP should
	 * 		be retreived.
	 * @return string A string containing the raw PHP of the specified view.
	 * @throws NoSuchViewException if the specified view cannot be found or
	 * 		loaded.
	 */
	protected static function getViewPHP($viewName) {
		$engine = Config::getVal("view", "engine") ?: static::DEFAULT_ENGINE;
		$engineClass = '\hydrogen\view\engines\\' . $engine . '\\' .
			ucfirst($engine) . 'Engine';
		$loader = LoaderFactory::getLoader();
		return $engineClass::getPHP($viewName, $loader);
	}
	
	/**
	 * This class should not be instantiated.
	 */
	protected function __construct() {}
	
}

?>