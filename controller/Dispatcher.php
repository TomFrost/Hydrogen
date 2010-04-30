<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\controller;

use hydrogen\controller\exceptions\NoSuchMethodException;
use hydrogen\controller\exceptions\MissingArgumentException;

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
	const RULE_POSTVAR_MAP = 9;
	const RULE_POSTVAR_MATCH = 10;
	const RULE_POSTVAR_REGEX_MATCH = 11;
	const RULE_URL_REGEX_MAP = 12;
	const RULE_URL_REGEX_MATCH = 13;
	const RULE_MATCH_ALL = 14;
	
	protected static $dispatchRules = array();
	protected static $controllerPaths = array();
	protected static $oldHandler = false;
	
	public static function dispatch($defaultNamespace='\\', $defaultSuffix=false) {
		$handled = false;
		if (count(static::$dispatchRules) === 0)
			return static::dispatchPathInfoAutoMap($defaultNamespace, $defaultSuffix);
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
						$rule[1]['namespace'] ?: $defaultNamespace,
						$rule[1]['suffix'] ?: $defaultSuffix
						);
					break;
				case self::RULE_PATHINFO_FOLDER_MAP:
					$handled = static::dispatchPathInfoFolderMap(
						$rule[1]['cIndex'],
						$rule[1]['fIndex'],
						$rule[1]['aIndex'],
						$rule[1]['namespace'] ?: $defaultNamespace,
						$rule[1]['suffix'] ?: $defaultSuffix
						);
					break;
				case self::RULE_PATHINFO_REGEX_MAP:
					$handled = static::dispatchPathInfoRegexMap(
						$rule[1]['regex'],
						$rule[1]['cIndex'],
						$rule[1]['fIndex'],
						$rule[1]['aIndex'],
						$rule[1]['namespace'] ?: $defaultNamespace,
						$rule[1]['suffix'] ?: $defaultSuffix
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
						$rule[1]['namespace'] ?: $defaultNamespace,
						$rule[1]['suffix'] ?: $defaultSuffix
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
				case self::RULE_POSTVAR_MAP:
					$handled = static::dispatchPostVarMap(
						$rule[1]['cVar'],
						$rule[1]['fVar'],
						$rule[1]['aVar'],
						$rule[1]['namespace'] ?: $defaultNamespace,
						$rule[1]['suffix'] ?: $defaultSuffix
						);
					break;
				case self::RULE_POSTVAR_MATCH:
					$handled = static::dispatchPostVarMatch(
						$rule[1]['match'],
						$rule[1]['cName'],
						$rule[1]['fName'],
						$rule[1]['aVar']
						);
					break;
				case self::RULE_POSTVAR_REGEX_MATCH:
					$handled = static::dispatchPostVarRegexMatch(
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
						$rule[1]['namespace'] ?: $defaultNamespace,
						$rule[1]['suffix'] ?: $defaultSuffix
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
	
	public static function addPostVarMapRule($cVar, $fVar, $argVars, $namespace=false, $suffix=false) {
		static::addRule(self::RULE_POSTVAR_MAP,
			array(
				"cVar" => $cVar,
				"fVar" => $fVar,
				"aVar" => $argVars,
				"namespace" => $namespace,
				"suffix" => $suffix
				)
			);
	}

	public static function addPostVarMatchRule($matchArray, $cName, $fName, $argVars) {
		static::addRule(self::RULE_POSTVAR_MATCH,
			array(
				"match" => $matchArray,
				"cName" => $cName,
				"fName" => $fName,
				"aVar" => $argVars
				)
			);
	}

	public static function addPostVarRegexMatchRule($matchArray, $cName, $fName, $argVars) {
		static::addRule(self::RULE_POSTVAR_REGEX_MATCH,
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
			if (count($tokens) >= 3) {
				$args = array_slice($tokens, 3);
				return static::passRequest($tokens[1], $tokens[2], $args, $namespace, $suffix, true);
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
		
	}
	
	protected static function dispatchGetVarMatch($match, $cName, $fName, $aVar) {
		
	}
	
	protected static function dispatchGetVarRegexMatch($regex, $cName, $fName, $aVar) {
		
	}
	
	protected static function dispatchPostVarMap($cVar, $fVar, $aVar, $namespace, $suffix) {
		
	}
	
	protected static function dispatchPostVarMatch($match, $cName, $fName, $aVar) {
		
	}
	
	protected static function dispatchPostVarRegexMatch($regex, $cName, $fName, $aVar) {
		
	}
	
	protected static function dispatchUrlRegexMap($regex, $cIndex, $fIndex, $aIndex, $namespace, $suffix) {
		
	}
	
	protected static function dispatchUrlRegexMatch($regex, $cName, $fName, $aIndex) {
		
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