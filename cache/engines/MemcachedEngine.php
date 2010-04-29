<?php
/*
 * MemcachedEngine.php
 * by magik
 */

namespace hydrogen\cache\engines;

use Memcached;
use hydrogen\config\Config;
use hydrogen\cache\CacheEngine;

/**
 * CacheEngine to interface with the Memcache server.  It uses the 'memcached'
 * PECL extension.
 *
 * @requires memcached
 */
class MemcachedEngine implements CacheEngine {
	protected $memcached;
	
	public function __construct() {
		$res = new Memcached();
		$host = Config::getVal('cache', 'memcache_host') ?: 'localhost';
		$port = Config::getVal('cache', 'memcache_port') ?: 11211;
		$res->addServer($host, $port);
		$this->memcached = $res;
	}
	
//	public function __destruct() {
//		$this->memcached->close();
//	}

	public static function fixKeyWhiteSpace($key) {
		return str_replace(" ", "_", $key);
	}
	
	public function add($key, $value, $ttl) {
		$key = self::fixKeyWhiteSpace($key);
		return $this->memcached->add($key, $value, $ttl);
	}
	
	public function replace($key, $value, $ttl) {
		$key = self::fixKeyWhiteSpace($key);
		return $this->memcached->replace($key, $value, $ttl);
	}
	
	public function get($key) {
		$key = self::fixKeyWhiteSpace($key);
		return $this->memcached->get($key);
	}

	public function set($key, $value, $ttl) {
		$key = self::fixKeyWhiteSpace($key);
		return $this->memcached->set($key, $value, $ttl);
	}

	public function increment($key, $value=1) {
		$key = self::fixKeyWhiteSpace($key);
		return $this->memcached->increment($key, $value);
	}

	public function decrement($key, $value=1) {
		$key = self::fixKeyWhiteSpace($key);
		return $this->memcached->decrement($key, $value);
	}
	
	public function delete($key) {
		$key = self::fixKeyWhiteSpace($key);
		return $this->memcached->delete($key, 0);
	}
	
	public function deleteAll() {
		return $this->memcached->flush();
	}
	
	public function getStats() {
		return $this->memcached->getStats();
	}
}

?>
