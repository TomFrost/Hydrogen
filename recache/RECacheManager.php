<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\recache;

use hydrogen\config\Config;
use hydrogen\cache\CacheEngineFactory;
use hydrogen\semaphore\SemaphoreEngineFactory;
use hydrogen\recache\AssetWrapper;
use hydrogen\recache\RECacheWrapper;
use hydrogen\recache\TimeQueue;

class RECacheManager {
	const RECACHE_FAIL = 0;
	const RECACHE_SUCCESS = 1;
	const RECACHE_UPDATE = 2;
	
	protected static $manager = NULL;
	protected $engine, $semengine, $trackHits, $recacheWaiting, $pageCacheData;
	
	protected function __construct($trackHits=true) {
		$this->engine = CacheEngineFactory::getEngine();
		$this->semengine = SemaphoreEngineFactory::getEngine();
		$this->recacheWaiting = array();
		$this->trackHits = $trackHits;
	}
	
	public static function getInstance($trackHits=true) {
		if (is_null(static::$manager))
			static::$manager = new RECacheManager($trackHits);
		return static::$manager;
	}
	
	public function get($key, $allow_expired=false) {
		$ukey = $this->uniqueKey('ITEM', $key);
		$wrap = $this->engine->get($ukey);
		if ($wrap === false || !($wrap instanceof AssetWrapper)) {
			$this->get_miss();
			return false;
		}
		if (!$allow_expired) {
			if ($wrap->expire && $wrap->expire <= microtime(true)) {
				$this->get_miss();
				return false;
			}
			foreach ($wrap->groups as $group) {
				$gtime = $this->engine->get($this->uniqueKey('GROUP', $group));
				if ($gtime && $wrap->time <= $gtime) {
					$this->get_miss();
					return false;
				}
			}
		}
		$this->get_hit();
		return $wrap->data;
	}
	
	public function set($key, $value, $ttl=1800, $groups=false) {
		$wrap = new AssetWrapper($value, $ttl, $groups);
		$ukey = $this->uniqueKey('ITEM', $key);
		if ($this->engine->set($ukey, $wrap, $ttl + 30)) {
			if (($idx = array_search($key, $this->recacheWaiting)) !== false) {
				$this->semengine->release($this->uniqueKey('RECACHE', $key));
				unset($this->recacheWaiting[$idx]);
			}
			$this->set_hit();
			return true;
		}
		return false;
	}
	
	public function checkIfFrequent($num, $seconds, $key) {
		$tqkey = $this->uniqueKey('TQ', "${key}_${num}_${seconds}");
		$this->semengine->acquire($tqkey);
		$tq = $this->engine->get($tqkey);
		if (!$tq)
			$tq = new TimeQueue($num, $seconds);
		$isFrequent = $tq->hit();
		$this->engine->set($tqkey, $tq, $seconds + 5);
		$this->semengine->release($tqkey);
		return $isFrequent;
	}
	
	public function setIfFrequent($num, $seconds, $key, $value, $ttl=7200, $group=false) {
		$set = false;
		if ($this->checkIfFrequent($num, $seconds, $key))
			$set = $this->set($key, $value, $ttl, $group);
		if (($idx = array_search($key, $this->recacheWaiting)) !== false) {
			$this->semengine->release($this->uniqueKey('RECACHE', $key));
			unset($this->recacheWaiting[$idx]);
		}
		return $set;
	}
	
	public function increment($key, $value=1) {
		$success = $this->engine->increment($this->uniqueKey('ITEM', $key), $value);
		if ($success === false) {
			$success = $this->engine->set($this->uniqueKey('ITEM', $key), $value);
			if ($success)
				$success = $value;
		}
		return $success;
	}
	
	public function decrement($key, $value=1) {
		$success = $this->engine->decrement($this->uniqueKey('ITEM', $key), $value);
		if ($success === false) {
			$success = $this->engine->set($this->uniqueKey('ITEM', $key), 0);
			if ($success)
				$success = 0;
		}
		return $success;
	}
	
	public function clear($key) {
		return $this->engine->delete($this->uniqueKey('ITEM', $key));
	}
	
	public function clearGroup($group) {
		$key = $this->uniqueKey('GROUP', $group);
		$time = round(microtime(true), 4);
		return $this->engine->set($key, $time, 0);
	}
	
	public function clearAll() {
		return $this->engine->deleteAll();
	}
	
	public function getStats($addToStats=false) {
		$stats = $this->engine->getStats();
		if (!$stats)
			$stats = array();
		else if ($stats && !is_array($stats))
			$stats = array($stats);
		if ($addToStats !== false && !is_array($addToStats))
			$addToStats = array($addToStats);
		if ($this->trackHits) {
			$gethits = $this->engine->get($this->uniqueKey('STATS', 'get_hits'));
			$getmisses = $this->engine->get($this->uniqueKey('STATS', 'get_misses'));
			$sethits = $this->engine->get($this->uniqueKey('STATS', 'set_hits'));
			$stats['recache_get_hits'] = $gethits === false ? 0 : $gethits;
			$stats['recache_get_misses'] = $getmisses === false ? 0 : $getmisses;
			$stats['recache_set_hits'] = $sethits === false ? 0 : $sethits;
		}
		if (is_array($addToStats)) {
			foreach ($addToStats as $stat) {
				$value = $this->engine->get($this->uniqueKey('ITEM', $stat));
				if (!is_null($value) && isset($value->data))
					$stats[$stat] = $value->data;
			}
		}
		return $stats;
	}
	
	public function pageCache($ttl=1800, $key=false, $groups=false, $num=false, $seconds=false) {
		if (!$key) {
			$key = "http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
			if (count($_POST) > 0)
				$key .= serialize($_POST);
		}
		$pageContent = $this->get($key);
		if ($pageContent) {
			die($pageContent);
		}
		$this->pageCacheData = array(
			'ttl' => $ttl,
			'key' => $key,
			'groups' => $groups,
			'num' => $num,
			'seconds' => $seconds
			);
		ob_start(array($this, 'endPageCache'));
	}
	
	public function endPageCache($pageContent) {
		if (!is_array($this->pageCacheData))
			return '';
		if ($this->pageCacheData['num'] && $this->pageCacheData['seconds'])
			$this->setIfFrequent($this->pageCacheData['num'], $this->pageCacheData['seconds'], $this->pageCacheData['key'], 
				$pageContent, $this->pageCacheData['ttl'], $this->pageCacheData['groups']);
		else
			$this->set($this->pageCacheData['key'], $pageContent, $this->pageCacheData['ttl'], $this->pageCacheData['groups']);
		$this->pageCacheData = false;
		return $pageContent;
	}
	
	public function recache_pageCache($ttl=1800, $key=false, $groups=false, $failFunction=false) {
		if (!$key) {
			$key = "http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
			if (count($_POST) > 0)
				$key .= serialize($_POST);
		}
			
		$wrap = $this->recache_get_wrapper($key);
		switch ($wrap->response) {
			case self::RECACHE_SUCCESS:
				die($wrap->data);
			case self::RECACHE_UPDATE:
				$this->pageCacheData = array(
					'ttl' => $ttl,
					'key' => $key,
					'groups' => $groups,
					'num' => false,
					'seconds' => false
					);
				ob_start(array($this, 'endPageCache'));
				break;
			case self::RECACHE_FAIL:
				if (!$failFunction)
					$val = call_user_func($failFunction);
				else
					die();
		}
	}
	
	public function recache_get($key, $ttl, $groups, $updateFunction, 
			$updateFuncArgs=array(), $failFunction=false, $failFuncArgs=array()) {
		$wrap = $this->recache_get_wrapper($key);
		switch ($wrap->response) {
			case self::RECACHE_SUCCESS:
				return $wrap->data;
			case self::RECACHE_UPDATE:
				$val = call_user_func_array($updateFunction, $updateFuncArgs);
				$this->set($key, $val, $ttl, $groups);
				return $val;
			case self::RECACHE_FAIL:
				if ($failFunction)
					$val = call_user_func_array($failFunction, $failFuncArgs);
				else
					die('Server busy.  Please try again later.');
		}
		return NULL;
	}
	
	public function recache_get_wrapper($key) {
		// First we make the wrapper
		$wrap = new RECacheWrapper();
		
		// Get the request. If the cache has it, return it with SUCCESS.
		$val = $this->get($key);
		if ($val !== false) {
			$wrap->response = self::RECACHE_SUCCESS;
			$wrap->data = $val;
			return $wrap;
		}
		
		// The cache doesn't have it.  If it's not currently being updated, send the UPDATE response.
		$semkey = $this->uniqueKey('RECACHE', $key);
		if ($this->semengine->acquire($semkey, 0)) {
			$this->recacheWaiting[] = $key;
			$wrap->response = self::RECACHE_UPDATE;
			return $wrap;
		}
		
		// It's currently being updated.  If we have old data available, use that for now.
		$val = $this->get($key, true);
		if ($val !== false) {
			$wrap->response = self::RECACHE_SUCCESS;
			$wrap->data = $val;
			return $wrap;
		}
		
		// We don't have old data.  Wait for the key to be updated, and return the new data.
		if ($this->semengine->acquire($semkey)) {
			$this->semengine->release($semkey);
			$val = $this->get($key, true);
			if ($val !== false) {
				$wrap->response = self::RECACHE_SUCCESS;
				$wrap->data = $val;
				return $wrap;
			}
		}
		
		// The server still hasn't handled the update, meaning we're probably at risk of an overload.
		// Recommend an immediate die() so we don't crash out.
		$wrap->response = self::RECACHE_FAIL;
		return $wrap;
	}
	
	public function recache_cancel($key) {
		if (($idx = array_search($key, $this->recacheWaiting)) !== false) {
			$this->semengine->release($this->uniqueKey('RECACHE', $key));
			unset($this->recacheWaiting[$idx]);
			return true;
		}
		return false;
	}
	
	protected function uniqueKey($type, $key) {
		return Config::getRequiredVal('recache', 'unique_name') . ':' . $type . ':' . $key;
	}
	
	protected function get_miss() {
		if ($this->trackHits) {
			$key = $this->uniqueKey('STATS', 'get_misses');
			if (!$this->engine->increment($key, 1))
				$this->engine->set($key, 1, 0);
		}
	}
	
	protected function get_hit() {
		if ($this->trackHits) {
			$key = $this->uniqueKey('STATS', 'get_hits');
			if (!$this->engine->increment($key, 1))
				$this->engine->set($key, 1, 0);
		}
	}
	
	protected function set_hit() {
		if ($this->trackHits) {
			$key = $this->uniqueKey('STATS', 'set_hits');
			if (!$this->engine->increment($key, 1))
				$this->engine->set($key, 1, 0);
		}
	}
}

?>