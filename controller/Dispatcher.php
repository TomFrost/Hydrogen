<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\controller;

class Dispatcher {
	const RULE_PATHINFO_FOLDER_MAP = 0;
	const RULE_PATHINFO_REGEX_MAP = 1;
	const RULE_PATHINFO_REGEX_MATCH = 2;
	const RULE_QUERY_MAP = 3;
	const RULE_QUERY_MATCH = 4;
	const RULE_QUERY_REGEX_MATCH = 5;
	const RULE_URL_REGEX_MAP = 6;
	const RULE_URL_REGEX_MATCH = 7;
	
	protected static $dispatchRules = array();
	
	public static function addRule($type, $argArray) {
		static::$dispatchRules[] = array($type, $argArray);
	}
	
	public static function addPathInfoFolderMapRule($cIndex, $fIndex) {
		static::addRule(self::RULE_PATHINFO_FOLDER_MAP,
			array(
				"cIndex" => $cIndex,
				"fIndex" => $fIndex
				)
			);
	}
	
	public static function addPathInfoRegexMapRule($regex, $cIndex, $fIndex) {
		static::addRule(self::RULE_PATHINFO_REGEX_MAP,
			array(
				"regex" => $regex,
				"cIndex" => $cIndex,
				"fIndex" => $fIndex
				)
			);
	}
	
	public static function addPathInfoRegexMatchRule($regex, $cName, $fName) {
		static::addRule(self::RULE_PATHINFO_REGEX_MATCH,
			array(
				"regex" => $regex,
				"cName" => $cName,
				"fName" => $fName
				)
			);
	}
	
	public static function addQueryMapRule($cVar, $fVar) {
		static::addRule(self::RULE_QUERY_MAP,
			array(
				"cVar" => $cVar,
				"fVar" => $fVar
				)
			);
	}
	
	public static function addQueryMatchRule($matchArray, $excludeArray) {
		static::addRule(self::RULE_QUERY_MATCH,
			array(
				"match" => $matchArray,
				"exclude" => $excludeArray
				)
			);
	}
	
	public static function addQueryRegexMatchRule($matchArray, $excludeArray) {
		static::addRule(self::RULE_QUERY_REGEX_MATCH,
			array(
				"match" => $matchArray,
				"exclude" => $excludeArray
				)
			);
	}
	
	public static function addUrlRegexMapRule($regex, $cIndex, $fIndex) {
		static::addRule(self::RULE_URL_REGEX_MAP,
			array(
				"regex" => $regex,
				"cIndex" => $cIndex,
				"fIndex" => $fIndex
				)
			);
	}
	
	public static function addUrlRegexMatchRule($regex, $cName, $fName) {
		static::addRule(self::RULE_URL_REGEX_MATCH,
			array(
				"regex" => $regex,
				"cName" => $cName,
				"fName" => $fName
				)
			);
	}
	
	/**
	 * This class should not be instantiated.
	 */
	private function __construct() {}
}

?>