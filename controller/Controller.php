<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\controller;

use hydrogen\recache\RECacheManager;
use hydrogen\controller\exceptions\NoSuchMethodException;

/**
 * The abstract controller class.  This class should be extended by any controller
 * level class in a webapp.
 *
 * Similarly to the {@link \hydrogen\model\Model} class, classes that extend 
 * Controller are singletons with magic-caching built in.  Functions can be written 
 * into controller classes and called directly, just like normal.  However, functions 
 * can also be named with the magic caching naming convention to allow their output
 * to be automatically cached and delivered from the cache.
 *
 * For example, this function:
 *
 * <pre>
 * function hello($place) {
 *     echo "Hello, $place!";
 * }
 * </pre>
 *
 * Can be called as usual with no extra functionality attached to it.  However,
 * this function:
 *
 * <pre>
 * function hello__($place) {
 *     echo "Hello, $place!";
 * }
 * </pre>
 *
 * can be called with hello("world") as well as helloCached("world").  If called with
 * helloCached("world"), anything sent to stdout (in short, anything output to the web
 * browser) during the course of this function's execution will be cached using
 * Hydrogen's advanced RECache algorithm for 300 seconds -- five minutes.  During that 
 * time, calling helloCached("world") again will immediately output the cached data 
 * instead of executing the function.  Calling helloCached("universe") would call the 
 * function and cache the new output associated with the different argument.
 *
 * Note that any data returned by the function, any variables set by the function, 
 * etc., will not be cached -- only the output from this function from the time it's
 * called to the time it finishes execution is cached.  If the output of the function 
 * changes depending on what user loads it, for example, then that function should 
 * never be cached using this system.  Caching is useful for constant but complicated 
 * data that requires a great deal of processing, such as RSS feeds.
 *
 * While 300 is the default number of seconds for data to be cached, that can be
 * changed.  To cache the output for 600 seconds instead, the function can be
 * named like this:
 *
 * <pre>
 * function hello__600($place)
 * </pre>
 *
 * To take advantage of RECache's grouping feature, simply add any necessary group
 * names to the end of the function, with each name being separated by an underscore.
 * For example, to cache this function with the group "greetings":
 *
 * <pre>
 * function hello__600_greetings($place)
 * <pre>
 *
 * To cache it with the group "greetings" as well as the groups "text" and "english":
 *
 * <pre>
 * function hello__600_greetings_text_english($place);
 * </pre>
 *
 * Note that no matter how many seconds is set for the cache and no matter what or
 * how many groups are in the name, the function is still called with either
 * hello("world") for no caching, or helloCached("world") to apply the caching
 * options specified in the function name.
 *
 * For highest efficiency, every class that extends Controller in a single webapp
 * should have a protected static variable named $controllerID defined, whose value
 * is short as well as unique from every other controller in that web application.
 * For example:
 *
 * <pre>
 * class HelloController extends hydrogen\controller\Controller {
 *     protected static $controllerID = "hc";
 *
 *     // Functions here
 * }
 * </pre>
 *
 * This value will be used to cache output from cacheable functions in the controller,
 * distinguishing them from similarly-named functions in other controllers.  If the
 * value is not specified, the namespace and name of the controller-extending class
 * is used -- a slightly longer and less efficient name for caching purposes.
 */
abstract class Controller {
	protected static $instances = array();
	protected $cm;
	
	/**
	 * Controller is a singleton.  To get an instance of any Controller-extending
	 * class, see {@link #getInstance}.
	 */
	protected function __construct() {
		$this->cm = RECacheManager::getInstance();
	}
	
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
	 * The PHP magic method to intercept calls to functions that have magic caching
	 * enabled.  This function is responsible for collecting the output of controller
	 * functions and caching them, as well as returning that cached data and
	 * bypassing the controller function when the cached data exists.
	 *
	 * This function should never be called directly.
	 *
	 * @param func string The name of the function that was called.
	 * @param args array An array of arguments that the function was called with.
	 * @throws NoSuchMethodException if the specified function does not exist in
	 * 		this class with a magic-caching naming convention.
	 */
	public function __call($func, $args) {
		$methods = get_class_methods($this);
		$valids = array();
		$useCache = false;
		if (strrpos($func, 'Cached') === strlen($func) - 6) {
			$useCache = true;
			$func = substr($func, 0, -6);
		}
		$func .= '__';
		$success = false;
		foreach($methods as $method) {
			if (strpos($method, $func) === 0) {
				if (!$useCache)
					return call_user_func_array(array($this, $method), $args);
				else {
					$data = explode('_', substr($method, strlen($func)));
					$ttl = $data[0] !== '' ? $data[0] : 300;
					$groups = array();
					for ($i = 1; $i < count($data); $i++)
						$groups[] = &$data[$i];
					$key = 'C:' . (isset(static::$controllerID) ? static::$controllerID : get_class($this)) . '_' . substr($func, 0, -2);
					foreach($args as $arg)
						$key .= '_' . (is_bool($arg) ? ($arg ? '1' : '0') : $arg);
					echo $this->cm->recache_get($key, $ttl, $groups,
						array($this, "getOutput"),
						array(array($this, $method), $args));
					$success = true;
					break;
				}
			}
		}
		if ($success === false) {
			$class = get_class($this);
			throw new NoSuchMethodException("Method $func does not exist in controller $class.");
		}
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
	protected function getOutput($callback, $args) {
		ob_start();
		call_user_func_array($callback, $args);
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
}

?>