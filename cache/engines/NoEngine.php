<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\cache\engines;

use hydrogen\cache\CacheEngine;

/**
 * NoEngine is literally No Engine.  It can be used when no other cache engine can be used.
 * Each {@link hydrogen\cache\CacheEngine} method within it returns <code>false</code> except
 * for {@link #add}, which returns <code>true</code> in order for semaphore usage to not hang.
 */
class NoEngine implements CacheEngine {
	
	public function get($key) {
		return false;
	}
	
	public function add($key, $value, $ttl) {
		return true;
	}
	
	public function replace($key, $value, $ttl) {
		return false;
	}

	public function set($key, $value, $ttl) {
		return false;
	}

	public function increment($key, $value=1) {
		return false;
	}

	public function decrement($key, $value=1) {
		return false;
	}
	
	public function delete($key) {
		return false;
	}
	
	public function deleteAll() {
		return false;
	}
	
	public function getStats() {
		return false;
	}
	
	/**
	 * Retrieves the raw cache connection object that this cache engine wraps.
	 * This is useful to access in-depth caching features that Hydrogen may not
	 * be able to make available, but WILL eliminate compatibility with other
	 * cache engines.  Use this function if and only if a certain cache engine
	 * is required for the program to run, and if there is no plan to allow any
	 * alternatives to be used.
	 *
	 * @return mixed The raw connection object for this particular cache
	 * 		engine, or null if no such object exists.
	 */
	public function getRawEngine() {
		return null;
	}
}

?>