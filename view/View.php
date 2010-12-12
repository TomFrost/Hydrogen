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
			return static::$defaultContext->get($key);
		else {
			throw new NoSuchVariableException(
				"Variable does not exist in context: $key");
		}
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
	 * ViewSandbox.
	 *
	 * @param string viewName The name of the view to load.
	 * @param ViewSandbox sandbox The sandbox into which the view should be
	 * 		loaded.
	 */
	public static function loadIntoSandbox($viewName, $sandbox) {
		$engine = Config::getVal("view", "engine") ?: static::DEFAULT_ENGINE;
		$engineClass = '\hydrogen\view\engines\\' . $engine . '\\' .
			ucfirst($engine) . 'Engine';
		$loader = LoaderFactory::getLoader();
		$php = $engineClass::getPHP($viewName, $loader);
		$sandbox->loadRawPHP($php);
	}
	
	/**
	 * This class should not be instantiated.
	 */
	protected function __construct() {}
	
}

?>