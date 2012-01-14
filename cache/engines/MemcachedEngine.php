<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\cache\engines;

use Memcached;
use hydrogen\config\Config;
use hydrogen\cache\CacheEngine;

/**
 * CacheEngine to interface with the Memcache server.  It uses the 'Memcached'
 * PECL extension.
 *
 * @requires Memcached
 */
class MemcachedEngine implements CacheEngine {
	protected $memcached;
	
	public function __construct() {
		$pool = Config::getVal("cache", "pool_name");
		$mc = new Memcached($pool);
		if ($pool === false || count($mc->getServerList()) === 0) {
			$host = Config::getVal('cache', 'memcache_host') ?: 'localhost';
			$port = Config::getVal('cache', 'memcache_port') ?: 11211;
			$mc->addServer($host, $port);
		}
		$this->memcached = $mc;
	}
	
	public function add($key, $value, $ttl) {
		return $this->memcached->add($key, $value, $ttl);
	}
	
	public function replace($key, $value, $ttl) {
		return $this->memcached->replace($key, $value, $ttl);
	}
	
	public function get($key) {
		return $this->memcached->get($key);
	}

	public function set($key, $value, $ttl) {
		return $this->memcached->set($key, $value, $ttl);
	}

	public function increment($key, $value=1) {
		return $this->memcached->increment($key, $value);
	}

	public function decrement($key, $value=1) {
		return $this->memcached->decrement($key, $value);
	}
	
	public function delete($key) {
		return $this->memcached->delete($key, 0);
	}
	
	public function deleteAll() {
		return $this->memcached->flush();
	}
	
	public function getStats() {
		return $this->memcached->getStats();
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
		return $this->memcached;
	}
}

?>
