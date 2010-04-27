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
	
	public static function dispatch() {
		$handled = false;
		if (count($dispatchRules) === 0)
			return static::dispatchPathInfoAutoMap();
		foreach ($dispatchRules as $rule) {
			switch ($rule[0]) {
				case self::RULE_PATHINFO_AUTO_MAP:
					$handled = static::dispatchPathInfoAutoMap();
					break;
				case self::RULE_PATHINFO_FOLDER_MAP:
					$handled = static::dispatchPathInfoFolderMap(
						$rule[1]['cIndex'],
						$rule[1]['fIndex'],
						$rule[1]['aIndex']
						);
					break;
				case self::RULE_PATHINFO_REGEX_MAP:
					$handled = static::dispatchPathInfoRegexMap(
						$rule[1]['regex'],
						$rule[1]['cIndex'],
						$rule[1]['fIndex'],
						$rule[1]['aIndex']
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
						$rule[1]['aVar']
						);
					break;
				case self::RULE_GETVAR_MATCH:
					$handled = static::dispatchGetVarMatch(
						$rule[1]['match'],
						$rule[1]['cName'],
						$rule[1]['fName'],
						$rule[1]['aVar'],
						);
					break;
				case self::RULE_GETVAR_REGEX_MATCH:
					$handled = static::dispatchGetVarRegexMatch(
						$rule[1]['regex'],
						$rule[1]['cName'],
						$rule[1]['fName'],
						$rule[1]['aVar'],
						);
					break;
				case self::RULE_POSTVAR_MAP:
					$handled = static::dispatchPostVarMap(
						$rule[1]['cVar'],
						$rule[1]['fVar'],
						$rule[1]['aVar']
						);
					break;
				case self::RULE_POSTVAR_MATCH:
					$handled = static::dispatchPostVarMatch(
						$rule[1]['match'],
						$rule[1]['cName'],
						$rule[1]['fName'],
						$rule[1]['aVar'],
						);
					break;
				case self::RULE_POSTVAR_REGEX_MATCH:
					$handled = static::dispatchPostVarRegexMatch(
						$rule[1]['regex'],
						$rule[1]['cName'],
						$rule[1]['fName'],
						$rule[1]['aVar'],
						);
					break;
				case self::RULE_URL_REGEX_MAP:
					$handled = static::dispatchUrlRegexMap(
						$rule[1]['regex'],
						$rule[1]['cIndex'],
						$rule[1]['fIndex'],
						$rule[1]['aIndex']
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
	
	public static function addRule($type, $argArray=false) {
		static::$dispatchRules[] = array($type, $argArray);
	}
	
	public static function addRules($ruleArray) {
		static::$dispatchRules = array_merge(static::$dispatchRules, $ruleArray);
	}
	
	public static function addPathInfoAutoMapRule() {
		static::addRule(self::RULE_PATHINFO_AUTO_MAP);
	}
	
	public static function addPathInfoFolderMapRule($cIndex, $fIndex, $argIndexArray) {
		static::addRule(self::RULE_PATHINFO_FOLDER_MAP,
			array(
				"cIndex" => $cIndex,
				"fIndex" => $fIndex,
				"aIndex" => $argIndexArray
				)
			);
	}
	
	public static function addPathInfoRegexMapRule($regex, $cIndex, $fIndex, $argIndexArray) {
		static::addRule(self::RULE_PATHINFO_REGEX_MAP,
			array(
				"regex" => $regex,
				"cIndex" => $cIndex,
				"fIndex" => $fIndex,
				"aIndex" => $argIndexArray
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
	
	public static function addGetVarMapRule($cVar, $fVar, $argVars) {
		static::addRule(self::RULE_GETVAR_MAP,
			array(
				"cVar" => $cVar,
				"fVar" => $fVar,
				"aVar" => $argVars
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
	
	public static function addPostVarMapRule($cVar, $fVar, $argVars) {
		static::addRule(self::RULE_POSTVAR_MAP,
			array(
				"cVar" => $cVar,
				"fVar" => $fVar,
				"aVar" => $argVars
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
	
	public static function addUrlRegexMapRule($regex, $cIndex, $fIndex, $argIndexArray) {
		static::addRule(self::RULE_URL_REGEX_MAP,
			array(
				"regex" => $regex,
				"cIndex" => $cIndex,
				"fIndex" => $fIndex,
				"aIndex" => $argIndexArray
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
	
	protected static function dispatchPathInfoAutoMap() {
		if (!isset($_SERVER['PATH_INFO']))
			return false;
		$tokens = explode('/', $_SERVER['PATH_INFO']);
	}
	
	protected static function dispatchPathInfoFolderMap($cIndex, $fIndex, $aIndex) {
		
	}
	
	protected static function dispatchPathInfoRegexMap($regex, $cIndex, $fIndex, $aIndex) {
		
	}
	
	protected static function dispatchGetVarMap($cVar, $fVar, $aVar) {
		
	}
	
	protected static function dispatchGetVarMatch($match, $cName, $fName, $aVar) {
		
	}
	
	protected static function dispatchGetVarRegexMatch($regex, $cName, $fName, $aVar) {
		
	}
	
	protected static function dispatchPostVarMap($cVar, $fVar, $aVar) {
		
	}
	
	protected static function dispatchPostVarMatch($match, $cName, $fName, $aVar) {
		
	}
	
	protected static function dispatchPostVarRegexMatch($regex, $cName, $fName, $aVar) {
		
	}
	
	protected static function dispatchUrlRegexMap($regex, $cIndex, $fIndex, $aIndex) {
		
	}
	
	protected static function dispatchUrlRegexMatch($regex, $cName, $fName, $aIndex) {
		
	}
	
	/**
	 * This class should not be instantiated.
	 */
	private function __construct() {}
}

?>