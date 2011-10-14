<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\controller;

use hydrogen\recache\RECacheManager;
use hydrogen\common\exceptions\NoSuchMethodException;
use hydrogen\controller\exceptions\MissingArgumentException;
use hydrogen\controller\exceptions\RouteSyntaxException;

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
			$splitRegex = '`(%(?:(?:[a-zA-Z_][a-zA-Z0-9_\|]*)|{(?:[a-zA-Z_][a-zA-Z0-9_\|]*)}))`';
			$tokens = preg_split($splitRegex, $val, null,
				PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			var_dump($tokens);
			$set = array();
			// Iterate through each token to convert variables into arrays.
			// String literals stay as they are.
			foreach ($tokens as $segment) {
				if (preg_match($splitRegex, $segment)) {
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
			')(?:/|\(|$))\*?([a-zA-Z_][a-zA-Z0-9_]*)`', $path, $args);
		// Clean the args array
		if (isset($args[1]))
			$args = $args[1];
		else
			$args = array();
		// Turn restricted variables into named entities
		if ($restrictions) {
			foreach ($restrictions as $var => $regex) {
				$path = preg_replace('`(?<!\(\?):' . $var . '`',
					'(?P<' . $var . '>' . $regex . ')', $path);
			}
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
		$$this->ruleSet[] = array(
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
		// Get a proper PATH_INFO
		$path = isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] ?
			$_SERVER['PATH_INFO'] : '/';
		// Iterate through the rules until a match is found
		foreach ($this->ruleSet as $rule) {
			if ((!isset($rule['method']) || !$rule['method'] ||
					$rule['method'] == $_SERVER['REQUEST_METHOD']) &&
					preg_match($rule['regex'], $path, $vars)) {
				// Collect all the variables
				if (isset($rule['defaults']) && $rule['defaults'])
					$vars = array_merge($rule['defaults'], $vars);
				// Apply the transformations
				if (isset($rule['transforms'])) {
					foreach ($rule['transforms'] as $var => $val) {
						if (is_array($val)) {
							// Construct a value from the array elements
							$newVal = '';
							foreach ($val as $elem) {
								if (is_array($elem)) {
									if (!isset($vars[$elem[0]])) {
										throw new RouteSyntaxException(
											'Variable "' . $elem[0] .
											'" is required for the "' .
											$var . '" transform.');
									}
									$elem = $vars[array_shift($elem)];
									foreach ($elem as $filter) {
										switch ($filter) {
											case 'ucfirst':
												$elem = ucfirst($elem);
												break;
											case 'upper':
												$elem = strtoupper($elem);
												break;
											case 'lower':
												$elem = strtolower($elem);
												break;
											default:
												throw new RouteSyntaxException(
													'Filter "' . $filter .
													'" does not exist.'
												);
										}
									}
								}
								$newVal .= $elem;
							}
						}
						else if (is_string($val))
							$vars[$var] = $val;
						else {
							// TODO: Check for each transform type
						}
					}
				}
				// At this point, we must have a controller and function
				// TODO: Throw exceptions if they don't exist
			}
		}
		return false;
	}
}

?>