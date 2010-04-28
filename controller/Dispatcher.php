<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\controller;

class Dispatcher {
	const RULE_PATHINFO_AUTO_MAP = 0;
	const RULE_PATHINFO_FOLDER_MAP = 1;
	const RULE_PATHINFO_REGEX_MAP = 2;
	const RULE_PATHINFO_REGEX_MATCH = 3;
	const RULE_GETVAR_MAP = 4;
	const RULE_GETVAR_MATCH = 5;
	const RULE_GETVAR_REGEX_MATCH = 6;
	const RULE_POSTVAR_MAP = 7;
	const RULE_POSTVAR_MATCH = 8;
	const RULE_POSTVAR_REGEX_MATCH = 9;
	const RULE_URL_REGEX_MAP = 10;
	const RULE_URL_REGEX_MATCH = 11;
	
	protected static $dispatchRules = array();
	protected static $controllerPaths = array();
	
	public static function dispatch($defaultNamespace='\\', $defaultSuffix=false) {
		$handled = false;
		if (count(static::$dispatchRules) === 0)
			return static::dispatchPathInfoAutoMap($defaultNamespace, $defaultSuffix);
		foreach (static::$dispatchRules as $rule) {
			switch ($rule[0]) {
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
	
	protected static function passRequest($namespace, $suffix, $controller, $function, $args) {
		// Generate the fully qualified class name
		if ($namespace[0] !== '\\')
			$namespace = '\\' . $namespace;
		if ($namespace[strlen($namespace) - 1] !== '\\')
			$namespace .= '\\';
		$controller = ucfirst($controller) . $suffix;
		$class = $namespace . $controller;
		
		// Include the file if this class isn't loaded
		if (!class_exists($class) && isset($controllerPaths[$class]))
			\hydrogen\loadPath($controllerPaths[$class]);
			
		// Call it, Cap'n.
		$inst = $class::getInstance();
		call_user_func_array(array($inst, $function), $args);
		return true;
	}
	
	protected static function dispatchPathInfoAutoMap($namespace, $suffix) {
		if (isset($_SERVER['PATH_INFO'])) {
			$tokens = explode('/', $_SERVER['PATH_INFO']);
			if (count($tokens) >= 3) {
				$args = array_slice($tokens, 3);
				return static::passRequest($namespace, $suffix, $tokens[1], $tokens[2], $args);
			}
		}
		return false;
	}
	
	protected static function dispatchPathInfoFolderMap($cIndex, $fIndex, $aIndex, $namespace, $suffix) {
		if (isset($_SERVER['PATH_INFO'])) {
			$tokens = explode('/', $_SERVER['PATH_INFO']);
			if (isset($tokens[$cIndex]) && isset($tokens[$fIndex])) {
				$args = array();
				if (is_array($aIndex) && count($aIndex) > 0) {
					foreach ($aIndex as $i) {
						if (isset($tokens[$i]))
							$args[] = &$tokens[$i];
						else
							return false;
					}
				}
				return static::passRequest($namespace, $suffix, $tokens[$cIndex], 
					$tokens[$fIndex], $args);
			}
		}
		return false;
	}
	
	protected static function dispatchPathInfoRegexMap($regex, $cIndex, $fIndex, $aIndex, $namespace, $suffix) {
		if (isset($_SERVER['PATH_INFO'])) {
			if (preg_match($regex, $_SERVER['PATH_INFO'], $match) > 0) {
				if (isset($match[$cIndex]) && isset($match[$fIndex])) {
					$args = array();
					if (is_array($aIndex) && count($aIndex) > 0) {
						foreach ($aIndex as $i) {
							if (isset($match[$i]))
								$args[] = &$match[$i];
							else
								return false;
						}
					}
					return static::passRequest($namespace, $suffix, $match[$cIndex], 
						$match[$fIndex], $args);
				}
			}
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
	
	/**
	 * This class should not be instantiated.
	 */
	private function __construct() {}
}

?>