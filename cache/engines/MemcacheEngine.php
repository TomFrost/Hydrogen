<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\cache\engines;

use Memcache;
use hydrogen\config\Config;
use hydrogen\cache\CacheEngine;

/**
 * CacheEngine to interface with the Memcache server.  It uses the 'memcache'
 * PECL extension.
 *
 * @requires memcache
 */
class MemcacheEngine implements CacheEngine {
	protected $memcache;
	
	public function __construct() {
		$res = new Memcache();
		$host = Config::getVal('cache', 'memcache_host') ?: 'localhost';
		$port = Config::getVal('cache', 'memcache_port') ?: 11211;
		$res->addServer($host, $port);
		$this->memcache = $res;
	}
	
	public function __destruct() {
		$this->memcache->close();
	}
	
	public function add($key, $value, $ttl) {
		return $this->memcache->add($key, $value, false, $ttl);
	}
	
	public function replace($key, $value, $ttl) {
		return $this->memcache->replace($key, $value, false, $ttl);
	}
	
	public function get($key) {
		return $this->memcache->get($key);
	}

	public function set($key, $value, $ttl) {
		return $this->memcache->set($key, $value, false, $ttl);
	}

	public function increment($key, $value=1) {
		return $this->memcache->increment($key, $value);
	}

	public function decrement($key, $value=1) {
		return $this->memcache->decrement($key, $value);
	}
	
	public function delete($key) {
		return $this->memcache->delete($key, 0);
	}
	
	public function deleteAll() {
		return $this->memcache->flush();
	}
	
	public function getStats() {
		return $this->memcache->getStats();
	}
}

?>
