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
	
	const KEYWORD_CONTROLLER = 'controller';
	const KEYWORD_FUNCTION = 'function';
	
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
		if ($this->defaultTransforms)
			$this->defaultTransforms($this->defaultTransforms, $transforms);
		else
			$this->defaultTransforms = $transforms;
		return true;
	}
	
	public function catchAll($defaults, $transforms=array(), $argOrder=null,
			$argsAsArray=false) {
		// Early exit if we already have the rules set up
		if ($this->rulesFromCache)
			return false;
		// Construct the catch-all rule
		$ruleSet[] = array(
			'regex' => '`.*`',
			'defaults' => $defaults,
			'transforms' => $this->processTransforms($transforms),
			'args' => $argOrder,
			'argArray' => !!$argsAsArray
		);
		return true;
	}
	
	protected function processTransforms($transforms) {
		if (!$transforms)
			$transforms = array();
		if ($this->defaultTransforms)
			$transforms = array_merge($this->defaultTransforms, $transforms);
		// Iterate through transforms and break them up into segments
		foreach ($transforms as $var => $val) {
			// Break the string into variables and literals
			$tokens = preg_split(
				'`%(?:([a-zA-Z_][a-zA-Z0-9_\|]*)|{([a-zA-Z_][a-zA-Z0-9_\|]*)})`',
				$val, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			$set = array();
			// Iterate through each token to convert variables into arrays.
			// String literals stay as they are.
			foreach ($tokens as $segment) {
				if (preg_match('`^%[a-zA-Z_{]`', $segment[0])) {
					// This segment is a variable; put it in an array and add
					// any filters as additional array elements
					$segment = trim($segment, '%{}');
					if (strlen($segment)) {
						$segment = explode('|', $segment);
						$varBlock = array(array_shift($segment));
						foreach ($segment as $filter) {
							// Only allow legal filters
							switch ($filter) {
								case 'ucfirst':
								case 'upper':
								case 'lower':
									$varBlock[] = $filter;
									break;
								default:
									throw new RouteSyntaxException(
										"Illegal transform filter: '$filter'");
							}
						}
						// Add the array package to the set
						$set[] = $varBlock;
					}
				}
				else
					$set[] = $segment;
			}
			// If our set only contains a single string literal, save it
			// that way so that only variables are arrays.
			$transforms[$var] = count($set) == 1 && !is_array($set[0]) ?
				$set[0] : $set;
		}
		return $transforms;
	}
	
	protected function processPath($path, $restrictions=null, &$args=null) {
		// Turn the parentheses into non-capturing optional groups.
		$path = str_replace('(', '(?:', $path, $openParens);
		$path = str_replace(')', ')?', $path, $closeParens);
		if ($openParens !== $closeParens) {
			throw new RouterSyntaxException(
				"Unequal number of closing and opening parentheses in '" .
				$path . "'.");
		}
		// Collect our variables
		preg_match_all('`(?<!\(\?):(?!(?:' . self::KEYWORD_CONTROLLER .
			'|' . self::KEYWORD_FUNCTION .
			')(?:/|$))([a-zA-Z_][a-zA-Z0-9_]*)`', $path, $args);
		// Clean the args array
		if (isset($args[1]))
			$args = $args[1];
		else
			$args = array();
		// Turn restricted variables into named entities
		foreach ($restrictions as $var => $regex) {
			$path = preg_replace('`(?<!\(\?):' . $var . '`',
				'(?P<' . $var . '>' . $regex . ')', $path);
		}
		// Turn wildcard variables into named entities
		$path = preg_replace('`(?<!\(\?):\*([a-zA-Z_][a-zA-Z0-9_]*)`',
			'(?P<$1>.+)', $path);
		// Turn all other variables into named entities
		$path = preg_replace('`(?<!\(\?):([a-zA-Z_][a-zA-Z0-9_]*)`',
			'(?P<$1>[^/]+)', $path);
		return $path;
	}
	
	public function request($path, $defaults=null, $transforms=null,
			$restrictions=null, $argOrder=null, $argsAsArray=false,
			$httpMethod=null) {
		// Early exit if we already have the rules set up
		if ($this->rulesFromCache)
			return false;
		// Set up the new rule
		$ruleSet[] = array(
			'method' => $httpMethod ?: false,
			'regex' => $this->processPath($path, $restrictions, $args),
			'defaults' => $defaults,
			'transforms' => $this->processTransforms($transforms),
			'args' => $argOrder ?: $args,
			'argArray' => !!$argsAsArray
		);
		return true;
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