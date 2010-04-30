<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\controller;

use hydrogen\controller\exceptions\NoSuchMethodException;
use hydrogen\controller\exceptions\MissingArgumentException;

/**
 * The Dispatcher class processes a single page request with a list of rules that
 * determines to which controller the request should be sent.
 *
 * Using Dispatcher is a simple process.  The first step is defining dispatch rules,
 * which is done by calling the Dispatcher::add______Rule family of functions.  See the
 * documentation for those functions for details.  Once the rules have been defined,
 * {@link #dispatch} can be called to send the request to the appropriate
 * controller.
 *
 * When Dispatcher::dispatch() is called, each rule is checked in the order in which
 * it was set.  Once a rule matches the current request, the associated controller is
 * immediately triggered and no other rules are processed.
 *
 * There are two types of rules: Mapping rules and Matching rules.  A mapping rule should
 * be used when the name of the controller/function to be called is contained in the
 * url somewhere.  The most popular mapping rule (in this and other frameworks) is
 * the pathinfo auto map rule, which takes URLs like this:
 *
 * <pre>
 * http://mysite.com/myapp/index.php/blog/post/82/hi_there_everyone
 * </pre>
 *
 * And maps them to the Blog controller, calling the function post() with the
 * arguments "82" and "hi_there_everyone".  Optionally, a namespace and/or a class
 * suffix could be provided to trigger, for example, the myapp\controllers\BlogController
 * class instead of just the "Blog" class.  Note, however, that for mapping functions,
 * the first letter of the controller name is automatically capitalized when looking
 * for the matching controller class.  This is done to comply with popular naming
 * conventions for PHP, where all class names start with a capital letter.
 *
 * Matching rules trigger whenever certain conditions are met in the URL, and redirect
 * to a specified controller and function.  Often, arguments can be pulled from these
 * conditions and passed to the specified function.
 *
 * In the case where a class autoloader is not being used, paths to the controller
 * PHP files may be specified with the {@link #addControllerInclude} and
 * {@link #addControllerIncludes} commands.  The PHP file for a given controller is
 * included only when a rule with that controller is matched.
 *
 * If the Dispatcher fails to match the request to any of the rules, 
 * Dispatcher::dispatch() returns false.  At this point, a 404 page can be displayed
 * manually if that's the desired effect.  Another option is to set a "Match All" rule
 * as the final rule, which sends any request that hasn't matched any other rule to a
 * certain controller.  This controller could load a 404 page, for simplicity and
 * consistency.
 *
 * Remember, though, to always set a Home Match rule, or else direct loads to the
 * public-facing PHP file will be ignored!
 */
class Dispatcher {
	const RULE_HOME_MATCH = 0;
	const RULE_PATHINFO_AUTO_MAP = 1;
	const RULE_PATHINFO_FOLDER_MAP = 2;
	const RULE_PATHINFO_REGEX_MAP = 3;
	const RULE_PATHINFO_REGEX_MATCH = 4;
	const RULE_PATHINFO_MATCH = 5;
	const RULE_GETVAR_MAP = 6;
	const RULE_GETVAR_MATCH = 7;
	const RULE_GETVAR_REGEX_MATCH = 8;
	const RULE_URL_REGEX_MAP = 9;
	const RULE_URL_REGEX_MATCH = 10;
	const RULE_MATCH_ALL = 11;
	
	protected static $dispatchRules = array();
	protected static $controllerPaths = array();
	protected static $oldHandler = false;
	
	public static function dispatch() {
		$handled = false;
		foreach (static::$dispatchRules as $rule) {
			switch ($rule[0]) {
				case self::RULE_HOME_MATCH:
					$handled = static::dispatchHomeMatch(
						$rule[1]['cName'],
						$rule[1]['fName']
						);
					break;
				case self::RULE_PATHINFO_AUTO_MAP:
					$handled = static::dispatchPathInfoAutoMap(
						$rule[1]['namespace'],
						$rule[1]['suffix']
						);
					break;
				case self::RULE_PATHINFO_FOLDER_MAP:
					$handled = static::dispatchPathInfoFolderMap(
						$rule[1]['cIndex'],
						$rule[1]['fIndex'],
						$rule[1]['aIndex'],
						$rule[1]['namespace'],
						$rule[1]['suffix']
						);
					break;
				case self::RULE_PATHINFO_REGEX_MAP:
					$handled = static::dispatchPathInfoRegexMap(
						$rule[1]['regex'],
						$rule[1]['cIndex'],
						$rule[1]['fIndex'],
						$rule[1]['aIndex'],
						$rule[1]['namespace'],
						$rule[1]['suffix']
						);
					break;
				case self::RULE_PATHINFO_REGEX_MATCH:
					$handled = static::dispatchPathInfoRegexMatch(
						$rule[1]['regex'],
						$rule[1]['cName'],
						$rule[1]['fName'],
						$rule[1]['aIndex']
						);
					break;
				case self::RULE_PATHINFO_MATCH:
					$handled = static::dispatchPathInfoMatch(
						$rule[1]['match'],
						$rule[1]['cName'],
						$rule[1]['fName']
						);
					break;
				case self::RULE_GETVAR_MAP:
					$handled = static::dispatchGetVarMap(
						$rule[1]['cVar'],
						$rule[1]['fVar'],
						$rule[1]['aVar'],
						$rule[1]['namespace'],
						$rule[1]['suffix']
						);
					break;
				case self::RULE_GETVAR_MATCH:
					$handled = static::dispatchGetVarMatch(
						$rule[1]['match'],
						$rule[1]['cName'],
						$rule[1]['fName'],
						$rule[1]['aVar']
						);
					break;
				case self::RULE_GETVAR_REGEX_MATCH:
					$handled = static::dispatchGetVarRegexMatch(
						$rule[1]['regex'],
						$rule[1]['cName'],
						$rule[1]['fName'],
						$rule[1]['aVar']
						);
					break;
				case self::RULE_URL_REGEX_MAP:
					$handled = static::dispatchUrlRegexMap(
						$rule[1]['regex'],
						$rule[1]['cIndex'],
						$rule[1]['fIndex'],
						$rule[1]['aIndex'],
						$rule[1]['namespace'],
						$rule[1]['suffix']
						);
					break;
				case self::RULE_URL_REGEX_MATCH:
					$handled = static::dispatchUrlRegexMatch(
						$rule[1]['regex'],
						$rule[1]['cName'],
						$rule[1]['fName'],
						$rule[1]['aIndex']
						);
					break;
				case self::RULE_MATCH_ALL:
					$handled = static::dispatchMatchAll(
						$rule[1]['cName'],
						$rule[1]['fName']
						);
					break;
			}
			if ($handled === true)
				return true;
		}
		return false;
	}
	
	public static function addControllerInclude($controllerName, $phpPath) {
		static::$controllerPaths[$controllerName] = $phpPath;
	}
	
	public static function addControllerIncludes($arrayMap) {
		static::$controllerPaths = array_merge(static::$controllerPaths, $arrayMap);
	}
	
	public static function addRule($type, $argArray=false) {
		static::$dispatchRules[] = array($type, $argArray);
	}
	
	public static function addRules($ruleArray) {
		static::$dispatchRules = array_merge(static::$dispatchRules, $ruleArray);
	}
	
	public static function addHomeMatchRule($cName, $fName) {
		static::addRule(self::RULE_HOME_MATCH,
			array(
				"cName" => $cName,
				"fName" => $fName
				)
			);
	}
	
	public static function addPathInfoAutoMapRule($namespace=false, $suffix=false) {
		static::addRule(self::RULE_PATHINFO_AUTO_MAP,
			array(
				"namespace" => $namespace,
				"suffix" => $suffix
				)
			);
	}
	
	public static function addPathInfoFolderMapRule($cIndex, $fIndex, $argIndexArray, $namespace=false, $suffix=false) {
		static::addRule(self::RULE_PATHINFO_FOLDER_MAP,
			array(
				"cIndex" => $cIndex,
				"fIndex" => $fIndex,
				"aIndex" => $argIndexArray,
				"namespace" => $namespace,
				"suffix" => $suffix
				)
			);
	}
	
	public static function addPathInfoRegexMapRule($regex, $cIndex, $fIndex, $argIndexArray, $namespace=false, $suffix=false) {
		static::addRule(self::RULE_PATHINFO_REGEX_MAP,
			array(
				"regex" => $regex,
				"cIndex" => $cIndex,
				"fIndex" => $fIndex,
				"aIndex" => $argIndexArray,
				"namespace" => $namespace,
				"suffix" => $suffix
				)
			);
	}
	
	public static function addPathInfoRegexMatchRule($regex, $cName, $fName, $argIndexArray) {
		static::addRule(self::RULE_PATHINFO_REGEX_MATCH,
			array(
				"regex" => $regex,
				"cName" => $cName,
				"fName" => $fName,
				"aIndex" => $argIndexArray
				)
			);
	}
	
	public static function addPathInfoMatchRule($match, $cName, $fName) {
		static::addRule(self::RULE_PATHINFO_REGEX_MATCH,
			array(
				"match" => $match,
				"cName" => $cName,
				"fName" => $fName
				)
			);
	}
	
	public static function addGetVarMapRule($cVar, $fVar, $argVars, $namespace=false, $suffix=false) {
		static::addRule(self::RULE_GETVAR_MAP,
			array(
				"cVar" => $cVar,
				"fVar" => $fVar,
				"aVar" => $argVars,
				"namespace" => $namespace,
				"suffix" => $suffix
				)
			);
	}
	
	public static function addGetVarMatchRule($matchArray, $cName, $fName, $argVars) {
		static::addRule(self::RULE_GETVAR_MATCH,
			array(
				"match" => $matchArray,
				"cName" => $cName,
				"fName" => $fName,
				"aVar" => $argVars
				)
			);
	}
	
	public static function addGetVarRegexMatchRule($matchArray, $cName, $fName, $argVars) {
		static::addRule(self::RULE_GETVAR_REGEX_MATCH,
			array(
				"match" => $matchArray,
				"cName" => $cName,
				"fName" => $fName,
				"aVar" => $argVars
				)
			);
	}
	
	public static function addUrlRegexMapRule($regex, $cIndex, $fIndex, $argIndexArray, $namespace=false, $suffix=false) {
		static::addRule(self::RULE_URL_REGEX_MAP,
			array(
				"regex" => $regex,
				"cIndex" => $cIndex,
				"fIndex" => $fIndex,
				"aIndex" => $argIndexArray,
				"namespace" => $namespace,
				"suffix" => $suffix
				)
			);
	}
	
	public static function addUrlRegexMatchRule($regex, $cName, $fName, $argIndexArray) {
		static::addRule(self::RULE_URL_REGEX_MATCH,
			array(
				"regex" => $regex,
				"cName" => $cName,
				"fName" => $fName,
				"aIndex" => $argIndexArray
				)
			);
	}
	
	public static function addMatchAllRule($cName, $fName) {
		static::addRule(self::RULE_MATCH_ALL,
			array(
				"cName" => $cName,
				"fName" => $fName
				)
			);
	}
	
	public static function missingArgHandler($errno, $errstr, $errfile, $errline) {
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
	
	protected static function passRequest($controller, $function, $args=false, $namespace=false, $suffix=false, $argProtection=false) {
		// Generate the fully qualified class name
		if ($namespace !== false) {
			if ($namespace[0] !== '\\')
				$namespace = '\\' . $namespace;
			if ($namespace[strlen($namespace) - 1] !== '\\')
				$namespace .= '\\';
		}
		$controller = ucfirst($controller) . $suffix;
		$class = $namespace . $controller;
		
		// Include the file if this class isn't loaded
		if (!@class_exists($class) && isset($controllerPaths[$class]))
			\hydrogen\loadPath($controllerPaths[$class]);
			
		// Call it if everything's there
		if (@class_exists($class)) {	
			// Call it, Cap'n.
			$inst = $class::getInstance();
			if ($argProtection === true) {
				static::$oldHandler = set_error_handler(
					"\hydrogen\controller\Dispatcher::missingArgHandler",
					E_WARNING);
			}
			try {
				call_user_func_array(array($inst, $function), $args ?: array());
			}
			catch (NoSuchMethodException $e) {
				return false;
			}
			catch (MissingArgumentException $e) {
				return false;
			}
			return true;
		}
		return false;
	}
	
	protected static function getRequestedURL() {
		$url = 'http';
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
			$url .= 's';
		$url .= "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		return $url;
	}
	
	protected static function getArgsFromTokens($tokens, $aIndex) {
		$args = array();
		if (is_array($aIndex) && count($aIndex) > 0) {
			foreach ($aIndex as $i) {
				if (isset($tokens[$i]))
					$args[] = &$tokens[$i];
				else
					$args[] = false;
			}
		}
		return $args;
	}
	
	protected static function getArgsFromAssocArray($assoc, $keyArray) {
		$args = array();
		if (is_array($assoc) && count($assoc) > 0
				&& is_array($keyArray) && count($keyArray) > 0) {
			foreach ($keyArray as $key) {
				if (isset($assoc[$key]))
					$args[] = $assoc[$key];
				else
					$args[] = false;
			}
		}
		return $args;
	}
	
	protected static function dispatchMapFromTokens($tokens, $cIndex, $fIndex, $aIndex, $namespace, $suffix) {
		if (isset($tokens[$cIndex]) && isset($tokens[$fIndex])) {
			$args = static::getArgsFromTokens($tokens, $aIndex);
			return static::passRequest($tokens[$cIndex], $tokens[$fIndex], $args, $namespace, $suffix);
		}
		return false;
	}
	
	protected static function dispatchMatchFromTokens($tokens, $cName, $fName, $aIndex) {
		$args = static::getArgsFromTokens($tokens, $aIndex);
		return static::passRequest($cName, $fName, $args);
		return false;
	}
	
	protected static function dispatchHomeMatch($cName, $fName) {
		return static::dispatchPathInfoMatch('', $cName, $fName);
	}
	
	protected static function dispatchPathInfoAutoMap($namespace, $suffix) {
		if (isset($_SERVER['PATH_INFO'])) {
			$tokens = explode('/', $_SERVER['PATH_INFO']);
			if (count($tokens) >= 2) {
				if (count($tokens) > 3)
					$args = array_slice($tokens, 3);
				else
					$args = array();
				return static::passRequest(
					$tokens[1],
					isset($tokens[2]) ? $tokens[2] : "index",
					$args, $namespace, $suffix, true);
			}
		}
		return false;
	}
	
	protected static function dispatchPathInfoFolderMap($cIndex, $fIndex, $aIndex, $namespace, $suffix) {
		if (isset($_SERVER['PATH_INFO'])) {
			$tokens = explode('/', $_SERVER['PATH_INFO']);
			return static::dispatchMapFromTokens($tokens, $cIndex, $fIndex, $aIndex, $namespace, $suffix);
		}
		return false;
	}
	
	protected static function dispatchPathInfoRegexMap($regex, $cIndex, $fIndex, $aIndex, $namespace, $suffix) {
		if (isset($_SERVER['PATH_INFO'])) {
			if (preg_match($regex, $_SERVER['PATH_INFO'], $tokens) > 0) {
				return static::dispatchMapFromTokens($tokens, $cIndex, $fIndex, $aIndex,
					$namespace, $suffix);
			}
		}
		return false;
	}
	
	protected static function dispatchPathInfoRegexMatch($regex, $cName, $fName, $aIndex) {
		$pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
		if (preg_match($regex, $pathInfo, $tokens) > 0)
			return static::dispatchMatchFromTokens($tokens, $cName, $fName, $aIndex);
		return false;
	}
	
	protected static function dispatchPathInfoMatch($match, $cName, $fName) {
		if ((isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] === $match)
				|| (!isset($_SERVER['PATH_INFO']) && $match === '')) {
			return static::passRequest($cName, $fName);
		}
		return false;
	}
	
	protected static function dispatchGetVarMap($cVar, $fVar, $aVar, $namespace, $suffix) {
		if (isset($_GET[$cVar]) && isset($_GET[$fVar])) {
			return static::passRequest(
				$_GET[$cVar],
				$_GET[$fVar],
				static::getArgsFromAssocArray($_GET, $aVar),
				$namespace,
				$suffix);
		}
		return false;
	}
	
	protected static function dispatchGetVarMatch($match, $cName, $fName, $aVar) {
		foreach ($match as $key => $val) {
			if (!isset($_GET[$key]) || $_GET[$key] != $val)
				return false;
		}
		return static::passRequest($cName, $fName,
			static::getArgsFromAssocArray($_GET, $aVar));
	}
	
	protected static function dispatchGetVarRegexMatch($regex, $cName, $fName, $aVar) {
		foreach ($regex as $key => $val) {
			if (!isset($_GET[$key]) || !preg_match($val, $_GET[$key]))
				return false;
		}
		return static::passRequest($cName, $fName,
			static::getArgsFromAssocArray($_GET, $aVar));
	}
	
	protected static function dispatchUrlRegexMap($regex, $cIndex, $fIndex, $aIndex, $namespace, $suffix) {
		if (preg_match($regex, statis::getRequestedURL(), $matches)) {
			return static::dispatchMapFromTokens($matches, $cIndex, $fIndex,
				$aIndex, $namespace, $suffix);
		}
		return false;
	}
	
	protected static function dispatchUrlRegexMatch($regex, $cName, $fName, $aIndex) {
		if (preg_match($regex, statis::getRequestedURL(), $matches))
			return static::dispatchMatchFromTokens($matches, $cName, $fName, $aIndex);
		return false;
	}
	
	protected static function dispatchMatchAll($cName, $fName) {
		return static::passRequest($cName, $fName);
	}
	
	/**
	 * This class should not be instantiated.
	 */
	private function __construct() {}
}

?>