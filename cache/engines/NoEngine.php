<?php
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
}

?>