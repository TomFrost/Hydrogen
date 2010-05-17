<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\controller;

use hydrogen\common\MagicCacheable;
use hydrogen\recache\RECacheManager;

/**
 * Controller provides a baseline for how Controller classes should be
 * constructed.  It offers no data manipulation functionality of its own, but
 * includes two patterns that should be used in every extending class: the
 * singleton pattern (letting only one instance of each Controller-extending
 * class exist) and the MagicCacheable pattern (allowing controller functions'
 * output to be cached, based on what those functions are named).
 *
 * Controller-extending classes should never be instantiated with the 'new'
 * keyword.  Rather, an instance should be gotten with the {@link #getInstance}
 * method, like this:
 *
 * <pre>
 * $blogCtrl = \myapp\BlogController::getInstance();
 * </pre>
 *
 * For instructions on how to name functions within the extended Controller to
 * allow their output to be cached automatically, see
 * {@link hydrogen\common\MagicCacheable}.
 *
 * When the "Cached" version of a function is called and no cached data is
 * found for that key, Controller executes the function and listens to any
 * output to stdout for its duration.  This output, then, is cached.
 *
 * Similarly, when cached data is found, that data is immediately sent to
 * stdout (in most cases, the web browser) rather than being returned.  It
 * will respect all currently active buffering.
 *
 * All functions named with the MagicCacheable naming convention will return
 * true upon completion, whether they're called with "Cached" added or not.
 * For this reason, any controller functions for which the return value
 * is important should not utilize magic-caching.  Consider the Model class
 * for any such functions.
 */
abstract class Controller extends MagicCacheable {
	protected static $instances = array();
	
	/**
	 * Controller is a singleton.  To get an instance of any
	 * Controller-extending class, see {@link #getInstance}.
	 */
	protected function __construct() { }
	
	/**
	 * Creates an instance of this Controller if one has not yet been created,
	 * or returns the already-created instance if it has.
	 *
	 * @return The singleton instance of this Controller object.
	 */
	public static function getInstance() {
		$class = get_called_class();
		if (!isset(static::$instances[$class]))
			static::$instances[$class] = new $class();
		return static::$instances[$class];
	}
	
	/**
	 * Calls the RECache algorithm to facilitate the magic-caching of a
	 * controller function's output.
	 *
	 * @param key string The key name to be cached.
	 * @param ttl int The number of seconds until this key should expire.
	 * @param groups array|string|boolean The group name, or array of group
	 * 		names, to associate the key with; or false to not associate the
	 * 		key with any group.
	 * @param callback callback A properly formatted callback to a function
	 * 		within this (extended) class.  The result of calling this function
	 * 		will be cached.
	 * @param args array|boolean An array of arguments to send to the callback
	 * 		function, or false for no arguments.
	 * @return boolean true.
	 */
	protected function __recache(&$key, &$ttl, &$groups, $callback, &$args) {
		$cm = RECacheManager::getInstance();
		echo $cm->recache_get($key, $ttl, $groups,
			array($this, "__getOutput"),
			array($callback, $args));
		return true;
	}
	
	/**
	 * Executes the specified function with the given arguments, intercepting all
	 * of the stdout output that occurs during the duration of its execution.
	 *
	 * @param callback callback The function to call, in standard PHP callback format.
	 * @param args array An array of arguments with which to call the specified 
	 * 		function.
	 * @return The output sent during the duration of the specified function's
	 * 		execution.
	 */
	public function __getOutput($callback, $args) {
		ob_start();
		call_user_func_array($callback, $args);
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
}

?>