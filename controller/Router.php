<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\controller;

use hydrogen\recache\RECacheManager;
use hydrogen\common\exceptions\NoSuchMethodException;
use hydrogen\controller\exceptions\MissingArgumentException;

/**
 * TODO: Documentation
 */
class Router {
	
	const DEFAULT_CACHE_TIME = 900;
	
	const TRANSFORM_EXPAND_ARRAY = 1;
	const TRANSFORM_EXPAND_PARAMS = 2;
	
	protected $defaultTransforms;
	protected $ruleSet = array();
	protected $rulesFromCache = false;
	protected $name, $expireTime, $groups;
	
	public function __construct($name=null, $expireTime=null, $groups=null) {
		$this->name = $name;
		$this->expireTime = $expireTime;
		$this->groups = $groups;
		
		// Attempt to get a cached version of this rule set
		if ($name) {
			$cm = RECacheManager::getInstance();
			$rules = $cm->get("Router:$name", false);
			if ($rules) {
				$this->ruleSet = &$rules;
				$this->rulesFromCache = true;
			}
		}
	}
	
	public function addDefaultTransforms($transforms) {
		// Early exit if we already have the rules set up
		if ($this->rulesFromCache)
			return false;
		// Merge in the rules
		$this->defaultTransforms($this->defaultTransforms, $transforms);
		return true;
	}
	
	public function catchAll($defaults, $transforms=array(), $argOrder=null,
			$arsAsArray=false) {
		// Early exit if we already have the rules set up
		if ($this->rulesFromCache)
			return false;
		// Construct the catch-all rule
		if ($this->defaultTransforms)
			$transforms = array_merge($this->defaultTransforms, $transforms);
		$ruleSet[] = array(
			'regex' => '`.*`',
			'defaults' => $defaults,
			'transforms' => $this->parseTransforms($transforms),
			'args' => $argOrder,
			'argArray' => !!$argOrder
		);
		return true;
	}
	
	protected function parseTransforms($transforms) {
		// TODO: Iterate through transforms and break them up into segments
	}
	
	public function request($url, $defaults=null, $transforms=null,
			$restrictions=null, $argOrder=null, $argsAsArray=false,
			$httpMethod=null) {
		// Early exit if we already have the rules set up
		if ($this->rulesFromCache)
			return false;
		// TODO: Set up the new rule
		
	}
	
	public function start() {
		// If we need to cache the rules, do so
		if (!$this->rulesFromCache && $this->name) {
			$cm = RECacheManager::getInstance();
			$cm->set("Router:$name", $this->ruleSet,
				$this->expireTime !== null ? $this->expireTime :
				self::DEFAULT_CACHE_TIME, $this->groups);
		}
		// TODO: Iterate through the rules until a match is found
	}
}

?>