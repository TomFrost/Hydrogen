<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\cache\engines;

use Redis;
use hydrogen\config\Config;
use hydrogen\cache\CacheEngine;

/**
 * CacheEngine to interface with the Redis server.  It uses the 'php-redis'
 * extension.
 *
 * @requires php-redis https://github.com/nicolasff/phpredis
 */
class RedisEngine implements CacheEngine {
	
	/**
	 * The instance of the underlying engine wrapped by this class.
	 */
	protected $engine;
	
	/**
	 * Construct a new RedisEngine.
	 */
	public function __construct() {
		$pooling = Config::getVal("cache", "use_pooling");
		$server = Config::getRequiredVal("cache", "server");
		$port = Config::getVal("cache", "port") ?: 6379;
		$timeout = Config::getVal("cache", "timeout") ?: null;
		$password = Config::getVal("cache", "password");
		$this->engine = new Redis();
		if ($pooling)
			$this->engine->pconnect($server, $port, $timeout);
		else
			$this->engine->connect($server, $port, $timeout);
		if ($password)
			$this->engine->auth($password);
	}
	
	/**
	 * Adds a new key/value pair to the cache, only if the key does not currently exist.
	 *
	 * @param string key The key to add
	 * @param mixed value The value to add
	 * @param int ttl The number of seconds until the key/value pair should expire
	 * @return boolean <code>true</code> if the key was successfully created; <code>false</code> otherwise.
	 */
	public function add($key, $value, $ttl) {
		$success = $this->engine->setnx($key, $value);
		if ($success && $ttl)
			$this->engine->setTimeout($key, $ttl);
		return $success;
	}
	
	/**
	 * Replaces an existing key/value pair in the cache.
	 *
	 * @param string key The key to replace
	 * @param mixed value The new value for this key
	 * @param int ttl The number of seconds until the key/value pair should expire
	 * @return boolean <code>true</code> if the key was successfully replaced; <code>false</code> otherwise.
	 */
	public function replace($key, $value, $ttl) {
		if ($this->engine->exists($key))
			return $this->engine->setex($key, $ttl, $value);
		return false;
	}
	
	/**
	 * Gets the value of the specified key.
	 *
	 * @param string key The key for which to retrieve the value.
	 * @return mixed The value stored for this key.  If the key doesn't exist, <i>false</i>
	 * 		is returned.
	 */
	public function get($key) {
		return $this->engine->get($key);
	}
	
	/**
	 * Sets the value of the specified key, regardless of whether or not it already exists.
	 *
	 * @param string key The key to set
	 * @param mixed value The value for this key
	 * @param int ttl The number of seconds until the key/value pair should expire
	 * @return boolean <code>true</code> if the key was successfully set; <code>false</code> otherwise.
	 */
	public function set($key, $value, $ttl) {
		return $this->engine->setex($key, $ttl, $value);
	}
	
	/**
	 * Increments the value of a stored integer.
	 *
	 * @param string key The key whose value should be incremented
	 * @param int value The number by which to increase the current value.
	 * @return int The new value of the key, or <code>false</code> if the key does not exist, isn't
	 * 		an integer, or could not be incremented for any reason.
	 */
	public function increment($key, $value=1) {
		return $this->engine->incr($key, $value);
	}
	
	/**
	 * Decrements the value of a stored integer.
	 *
	 * @param string key The key whose value should be decremented
	 * @param int value The number by which to decrease the current value.
	 * @return int The new value of the key, or <code>false</code> if the key does not exist, isn't
	 * 		an integer, or could not be decremented for any reason.
	 */
	public function decrement($key, $value=1) {
		return $this->engine->decr($key, $value);
	}
	
	/**
	 * Immediately removes the specified key and its value from the cache.
	 *
	 * @param string key The key to remove from the cache.
	 * @return boolean <code>true</code> if the key was successfully deleted; <code>false</code> otherwise.
	 */
	public function delete($key) {
		return $this->engine->delete($key);
	}
	
	/**
	 * Flushes the cache entirely, removing every stored key/value pair.
	 *
	 * @return boolean <code>true</code> if the cache was successfully cleared; <code>false</code> otherwise.
	 */
	public function deleteAll() {
		return $this->engine->flushAll();
	}
	
	/**
	 * Retrieves an associative array of pertinent statistics for the cache engine in use.  Note that
	 * the statistics returned is dependent entirely on the engine, and follows no standard other than
	 * being in the format of an associative array.
	 *
	 * @return array An associative array of statistics specific to the cache engine. 
	 */
	public function getStats() {
		return $redis->info();
	}
}

?>