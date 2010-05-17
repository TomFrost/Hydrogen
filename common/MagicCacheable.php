<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\common;

use hydrogen\recache\RECacheManager;
use hydrogen\common\exceptions\NoSuchMethodException;

/**
 * The MagicCacheable abstract class allows any extending class to implement
 * a naming convention in its function names that allows their result, output,
 * or other associated information to be automatically cached using the
 * RECache algorithm in the {@link hydrogen\recache\RECacheManager}.
 *
 * Any functions in an extending class can be called directly without any
 * change in functionality.  The magic caching kicks in whenever two
 * underscores are added to the end of the function name, like this:
 *
 * <pre>
 * function getBlogPostsByPage__($pageNum) { ... }
 * </pre>
 *
 * Now, there are two functions that can be called:
 *
 * <pre>
 * // This version calls the function without using the cache
 * getBlogPostsByPage(2);
 *
 * // This version checks the cache for a result specific to the arguments
 * // that were passed in.  If it finds a value, it returns it without running
 * // the actual function.  If not, it runs the function, caches its return
 * // value (by default -- this can be changed with an override), and returns
 * // that value.
 * getBlogPostsByPageCached(2);
 * </pre>
 *
 * Simply calling the function with the word "Cached" (capital C!) at the end
 * of it triggers this functionality.
 *
 * By default, data is cached for 300 seconds -- five minutes.  To change this
 * value, simply tack the number of seconds on after the two underscores.  For
 * example, to change this to 600 seconds (ten minutes), simply name the
 * function like this:
 *
 * <pre>
 * function getBlogPostsByPage__600($pageNum) { ... }
 * </pre>
 *
 * RECache also supports "grouping" cached keys, allowing entire groups to be
 * manually expired all at once.  In order to add our example function to the
 * "pages" group when it's cached, simply add an underscore after the time
 * and follow it with the group name:
 *
 * <pre>
 * function getBlogPostsByPage__600_pages($pageNum) { ... }
 * </pre>
 *
 * RECache supports multiple groups as well.  For each additional group, add
 * an underscore and the group name.  This is an example of the function with
 * three groups:
 *
 * <pre>
 * function getBlogPostsByPage__600_pages_blog_posts($pageNum) { ... }
 * </pre>
 *
 * It's important to note that no matter what specifications are added to the
 * function name, the function is still called with either the original name
 * ("getBlogPostsByPage") or the original name with "Cached" appended
 * ("getBlogPostsByPageCached").
 *
 * By default, classes extending MagicCacheable will have the 'return' value
 * of any Cached function inserted into the cache.  Then, when the Cached
 * function is called, that value is returned.  However, this functionality
 * can be easily overridden to, instead, cache a transformed version of the
 * data, cache stdout output instead of 'return' data, echo data instead of
 * returning it, etc.
 *
 * The cache key used, by default, will include the entire class name (with
 * namespace) to assure that it is, in fact, unique.  To produce a much
 * shorter keyname, simply specify the protected static variable $classID --
 * a few-character string that is unique to any MagicCacheable-extending
 * class in the webapp.
 */
abstract class MagicCacheable {
	
	/**
	 * This PHP magic method is responsible for every facet of the magic
	 * caching algorithm but the interface with
	 * {@link hydrogen/recache/RECacheManager}.  As with any __call function,
	 * it should never be called directly -- but is rather triggered when
	 * an undefined function is called for the extending class.
	 *
	 * @param func string The name of the function that was called.
	 * @param args array An array of arguments sent to the specified function.
	 * @return mixed the return value of the specified function if a
	 * 		magic-caching-enabled version exists.
	 * @throws NoSuchMethodException if the specified method does not have
	 * 		a magic-caching-enabled counterpart.
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
					$key = (isset(static::$classID) ? static::$classID :
						get_class($this)) . ':' . substr($func, 0, -2);
					foreach($args as $arg) {
						$key .= ':' . (is_bool($arg) ?
							($arg ? '1' : '0') : $arg);
					}
					return $this->__recache($key, $ttl, $groups,
						array($this, $method), $args);
				}
			}
		}
		$class = get_class($this);
		throw new NoSuchMethodException(
			"Method $func does not exist in class $class.");
	}
	
	/**
	 * Calls the RECache algorithm to facilitate the magic-caching of a
	 * certain callback function.  By default, it caches the 'return' value of
	 * the callback function, and returns that data if it's found in the cache.
	 * This function can be overridden to cache, return, or output different
	 * data.
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
	 * @return mixed The cached value or the result of the callback function,
	 * 		depending on whether a value was found in the cache.
	 */
	protected function __recache(&$key, &$ttl, &$groups, $callback, &$args) {
		$cm = RECacheManager::getInstance();
		return $cm->recache_get($key, $ttl, $groups, $callback, $args);
	}
	
}

?>