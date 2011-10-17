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
	
	const TRANSFORM_EXPAND_ARRAY = '%{1}';
	const TRANSFORM_EXPAND_PARAMS = '%{2}';
	
	protected $globalOverrides;
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
	
	public function setGlobalOverrides($overrides) {
		// Early exit if we already have the rules set up
		if ($this->rulesFromCache)
			return false;
		$this->globalOverrides = $overrides;
		return true;
	}
	
	public function catchAll($defaults, $overrides=array(), $argOrder=null,
			$argsAsArray=false) {
		// Early exit if we already have the rules set up
		if ($this->rulesFromCache)
			return false;
		// Construct the catch-all rule
		$ruleSet[] = array(
			'regex' => '`.*`',
			'defaults' => $defaults,
			'overrides' => $this->processOverrides($overrides),
			'args' => $argOrder,
			'argArray' => !!$argsAsArray
		);
		return true;
	}
	
	public function delete($path, $defaults=null, $overrides=null,
			$restrictions=null, $argOrder=null, $argsAsArray=false) {
		return request($path, $defaults, $overrides, $restrictions,
			$argOrder, $argsAsArray, 'DELETE');
	}
	
	public function get($path, $defaults=null, $overrides=null,
			$restrictions=null, $argOrder=null, $argsAsArray=false) {
		return request($path, $defaults, $overrides, $restrictions,
			$argOrder, $argsAsArray, 'GET');
	}
	
	/**
	 * The handler used by the argument protection system in
	 * {@link #passRequest}.  Even though this argument is public for PHP
	 * error handling purposes, it should never be called directly.
	 *
	 * This error handler throws a {@link MissingArgumentException} whenever
	 * the error is, in fact, for a missing argument.  Otherwise, the handler
	 * re-throws the error as an E_USER_WARNING.
	 *
	 * @param int $errno The error type number.
	 * @param string $errstr A string describing the error.
	 * @param string $errfile The filename in which the error occurred.
	 * @param int $errline The line number on which the error occurred.
	 */
	public function missingArgHandler($errno, $errstr, $errfile,
			$errline) {
		$errCheck = "Missing argument";
		if ($errCheck === substr($errstr, 0, strlen($errCheck)))
			throw new MissingArgumentException();
		else {
			$caller = debug_backtrace();
			$caller = $caller[1];
			trigger_error($errstr . ' in <strong>' . $caller['function'] .
				'</strong> called from <strong>' . $caller['file'] . 
				'</strong> on line <strong>' . $caller['line'] .
				"</strong>\n<br />error handler", E_USER_WARNING);
		}
	}
	
	/**
	 * Attempts to pass the current page request to a specified controller,
	 * calling a function with a list of arguments.
	 *
	 * @param string $controller The class name of the controller to which the
	 * 		request should be passed.  This can either be a fully qualified
	 * 		class name with a namespace, or a simple name that can have
	 * 		a namespace prepended and a suffix appended to it later.
	 * @param string $function The name of the function inside of the controller
	 * 		to be called.
	 * @param array $args An array of arguments to be passed to the
	 * 		specified function, in order; null for no arguments.
	 * @param boolean $argProtection true to have this function return false if
	 * 		the specified function has more required arguments than what was
	 * 		included in the args array.  If false, this protection will be
	 * 		turned off and PHP's usual warning when a function with missing
	 * 		parameters is called will be fired.  Note that, if this is true,
	 * 		any warnings that are generated naturally by PHP will come as an
	 * 		E_USER_WARNING rather than an E_WARNING, due to limitations in
	 * 		PHP's error system.
	 * @return boolean true if the request was dispatched successfully,
	 * 		false otherwise.
	 */
	protected function passRequest($controller, $function, $args=null, 
			$argProtection=null) {
		// Only proceed if the controller exists
		if (@class_exists($controller)) {
			// Call it, Cap'n.
			$inst = $controller::getInstance();
			if ($argProtection) {
				set_error_handler(
					array($this, 'missingArgHandler'),
					E_WARNING);
			}
			try {
				call_user_func_array(array($inst, $function), $args ?: array());
			}
			catch (NoSuchMethodException $e) {
				if ($argProtection)
					restore_error_handler();
				return false;
			}
			catch (MissingArgumentException $e) {
				if ($argProtection)
					restore_error_handler();
				return false;
			}
			if ($argProtection)
				restore_error_handler();
			return true;
		}
		return false;
	}
	
	public function post($path, $defaults=null, $overrides=null,
			$restrictions=null, $argOrder=null, $argsAsArray=false) {
		return request($path, $defaults, $overrides, $restrictions,
			$argOrder, $argsAsArray, 'POST');
	}
	
	protected function processOverrides($overrides) {
		if (!$overrides)
			$overrides = array();
		if ($this->globalOverrides)
			$overrides = array_merge($this->globalOverrides, $overrides);
		// Iterate through overrides and break them up into segments
		foreach ($overrides as $var => $val) {
			// Break the string into variables and literals
			$splitRegex = '`(%(?:(?:[a-zA-Z_][a-zA-Z0-9_\|]*)|{(?:[a-zA-Z_][a-zA-Z0-9_\|]*)}))`';
			$tokens = preg_split($splitRegex, $val, null,
				PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
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
										"Illegal override filter: '$filter'");
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
			$overrides[$var] = count($set) == 1 && !is_array($set[0]) ?
				$set[0] : $set;
		}
		return $overrides;
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
		return '`' . $path . '`';
	}
	
	public function put($path, $defaults=null, $overrides=null,
			$restrictions=null, $argOrder=null, $argsAsArray=false) {
		return request($path, $defaults, $overrides, $restrictions,
			$argOrder, $argsAsArray, 'PUT');
	}
	
	public function request($path, $defaults=null, $overrides=null,
			$restrictions=null, $argOrder=null, $argsAsArray=false,
			$httpMethod=null) {
		// Early exit if we already have the rules set up
		if ($this->rulesFromCache)
			return false;
		// Set up the new rule
		$this->ruleSet[] = array(
			'method' => $httpMethod ?: false,
			'regex' => $this->processPath($path, $restrictions, $args),
			'defaults' => $defaults,
			'overrides' => $this->processOverrides($overrides),
			'args' => $argOrder ?: $args,
			'argArray' => !!$argsAsArray
		);
		return true;
	}
	
	public function start() {
		// If we need to cache the rules, do so
		if (!$this->rulesFromCache && $this->name) {
			$cm = RECacheManager::getInstance();
			$cm->set("Router:" . $this->name, $this->ruleSet,
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
				// Apply the overrides
				$arraysAsParams = array();
				if (isset($rule['overrides'])) {
					foreach ($rule['overrides'] as $var => $val) {
						if (is_array($val)) {
							// Construct a value from the array elements
							$newVal = '';
							foreach ($val as $elem) {
								if (is_array($elem)) {
									$varName = '';
									if (isset($vars[$elem[0]])) {
										$varName = $vars[array_shift($elem)];
										foreach ($elem as $filter) {
											switch ($filter) {
											case 'ucfirst':
												$varName = ucfirst($varName);
												break;
											case 'upper':
												$varName = strtoupper($varName);
												break;
											case 'lower':
												$varName = strtolower($varName);
												break;
											default:
												throw new RouteSyntaxException(
													'Filter "' . $filter .
													'" does not exist.'
												);
											}
										}
									}
									$elem = $varName;
								}
								$newVal .= $elem;
							}
							$vars[$var] = $newVal;
						}
						else {
							switch ($val) {
								case self::TRANSFORM_EXPAND_PARAMS:
									$arraysAsParams[$var] = true;
								case self::TRANSFORM_EXPAND_ARRAY:
									if (isset($vars[$var]))
										$vars[$var] = explode('/', $vars[$var]);
									break;
								default:
									$vars[$var] = $val;
							}
						}
					}
				}
				// At this point, we must have a controller and function
				if (!isset($vars[self::KEYWORD_CONTROLLER])) {
					throw new RouteSyntaxException(
						"Matched route is missing a '" .
						self::KEYWORD_CONTROLLER . "' variable.");
				}
				if (!isset($vars[self::KEYWORD_FUNCTION])) {
					throw new RouteSyntaxException(
						"Matched route is missing a '" .
						self::KEYWORD_FUNCTION . "' variable.");
				}
				// Collect the arguments to be sent to the function in the
				// requested format.
				$args = array();
				// Array keys format
				if (isset($rule['argArray']) && $rule['argArray']) {
					foreach ($rule['args'] as $key) {
						if (isset($vars[$key]))
							$args[$key] = $vars[$key];
					}
					$args = array($args);
				}
				// Parameters format
				else {
					foreach ($rule['args'] as $key) {
						if (isset($vars[$key])) {
							if (is_array($vars[$key]) &&
									isset($arraysAsParams[$key]))
								$args = array_merge($args, $vars[$key]);
							else
								$args[] = $vars[$key];
						}
					}
				}
				// Pass the request!
				$success = $this->passRequest($vars[self::KEYWORD_CONTROLLER],
					$vars[self::KEYWORD_FUNCTION], $args, true);
				if ($success)
					return true;
			}
		}
		return false;
	}
}

?>