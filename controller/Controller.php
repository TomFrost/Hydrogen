<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\controller;

use hydrogen\common\MagicCacheable;
use hydrogen\recache\RECacheManager;
use hydrogen\log\Log;

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
 * output to stdout for its duration.  This output is cached along with the
 * function's return value.
 *
 * Similarly, when cached data is found, that data is immediately sent to
 * stdout (in most cases, the web browser) and the cached return value
 * (if any) is returned.  It will respect all currently active buffering.
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
	 * @return Controller The singleton instance of this Controller object.
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
	 * @return mixed The return value of the callback function.
	 */
	protected function __recache(&$key, &$ttl, &$groups, $callback, &$args) {
		$cm = RECacheManager::getInstance();
		$data = $cm->recache_get($key, $ttl, $groups,
			array($this, "__getOutput"),
			array($callback, $args));
		if (count($data) === 2) {
			echo $data[1];
			return $data[0];
		}
		
		// In very rare cases, cached data might be wrong.
		Log::warn("Cached data for controller " . get_class($this) .
			" was not properly formatted.  Calling " .
			(isset($callback[1]) ? $callback[1] : "function") .
			" directly to recover.");
		return call_user_func_array($callback, $args);
	}
	
	/**
	 * Executes the specified function with the given arguments, intercepting
	 * all of the stdout output that occurs throughout the duration of its
	 * execution.
	 *
	 * @param callback callback The function to call, in standard PHP callback
	 * 		format.
	 * @param args array An array of arguments with which to call the specified 
	 * 		function.
	 * @return array An array, in which the first element is the function's
	 * 		return value, and the second element is the stdout output that was
	 * 		sent over the duration of the function's execution.
	 */
	public function __getOutput($callback, $args) {
		ob_start();
		$ret = call_user_func_array($callback, $args);
		$output = ob_get_contents();
		ob_end_clean();
		return array($ret, $output);
	}
}

?>