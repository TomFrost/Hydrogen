<?php
namespace hydrogen\cache;

/**
 * Interface to be implemented in all cache engines.  To create a new cache engine for a
 * currently unsupported cache system, simply implement this interface and write in each
 * method as it appears here.  Save it to the <i>engines</i> folder, and it can be referenced
 * by name from the config file without any other changes.
 */
interface CacheEngine {
	
	/**
	 * Adds a new key/value pair to the cache, only if the key does not currently exist.
	 *
	 * @param string key The key to add
	 * @param mixed value The value to add
	 * @param int ttl The number of seconds until the key/value pair should expire
	 * @return boolean <code>true</code> if the key was successfully created; <code>false</code> otherwise.
	 */
	public function add($key, $value, $ttl);
	
	/**
	 * Replaces an existing key/value pair in the cache.
	 *
	 * @param string key The key to replace
	 * @param mixed value The new value for this key
	 * @param int ttl The number of seconds until the key/value pair should expire
	 * @return boolean <code>true</code> if the key was successfully replaced; <code>false</code> otherwise.
	 */
	public function replace($key, $value, $ttl);
	
	/**
	 * Gets the value of the specified key.
	 *
	 * @param string key The key for which to retrieve the value.
	 * @return mixed The value stored for this key.  If the key doesn't exist, <i>false</i>
	 * 		is returned.
	 */
	public function get($key);
	
	/**
	 * Sets the value of the specified key, regardless of whether or not it already exists.
	 *
	 * @param string key The key to set
	 * @param mixed value The value for this key
	 * @param int ttl The number of seconds until the key/value pair should expire
	 * @return boolean <code>true</code> if the key was successfully replaced; <code>false</code> otherwise.
	 */
	public function set($key, $value, $ttl);
	
	/**
	 * Increments the value of a stored integer.
	 *
	 * @param string key The key whose value should be incremented
	 * @param int value The number by which to increase the current value.
	 * @return int The new value of the key, or <code>false</code> if the key does not exist, isn't
	 * 		an integer, or could not be incremented for any reason.
	 */
	public function increment($key, $value=1);
	
	/**
	 * Decrements the value of a stored integer.
	 *
	 * @param string key The key whose value should be decremented
	 * @param int value The number by which to decrease the current value.
	 * @return int The new value of the key, or <code>false</code> if the key does not exist, isn't
	 * 		an integer, or could not be decremented for any reason.
	 */
	public function decrement($key, $value=1);
	
	/**
	 * Immediately removes the specified key and its value from the cache.
	 *
	 * @param string key The key to remove from the cache.
	 * @return boolean <code>true</code> if the key was successfully deleted; <code>false</code> otherwise.
	 */
	public function delete($key);
	
	/**
	 * Flushes the cache entirely, removing every stored key/value pair.
	 *
	 * @return boolean <code>true</code> if the cache was successfully cleared; <code>false</code> otherwise.
	 */
	public function deleteAll();
	
	/**
	 * Retrieves an associative array of pertinent statistics for the cache engine in use.  Note that
	 * the statistics returned is dependent entirely on the engine, and follows no standard other than
	 * being in the format of an associative array.
	 *
	 * @return array An associative array of statistics specific to the cache engine. 
	 */
	public function getStats();
}

?>