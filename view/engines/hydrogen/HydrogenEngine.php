<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen;

use hydrogen\config\Config;
use hydrogen\view\TemplateEngine;
use hydrogen\view\engines\hydrogen\Parser;
use hydrogen\view\engines\hydrogen\exceptions\NoSuchFilterException;
use hydrogen\view\engines\hydrogen\exceptions\NoSuchTagException;

class HydrogenEngine implements TemplateEngine {
	
	protected static $filterClass = array();
	protected static $filterPath = array();
	protected static $filterNamespace = array(
		'\hydrogen\view\engines\hydrogen\filters\\'
		);
	protected static $tagClass = array();
	protected static $tagPath = array();
	protected static $tagNamespace = array(
		'\hydrogen\view\engines\hydrogen\tags\\'
		);

	public static function addFilter($filterName, $className, $path=false) {
		$filterName = strtolower($filterName);
		static::$filterClass[$filterName] = $className;
		if ($path)
			static::$filterPath[$filterName] = Config::getAbsolutePath($path);
	}
	
	public static function addFilterNamespace($namespace) {
		static::$filterNamespace[] = static::formatNamespace($namespace);
	}
	
	public static function addTag($tagName, $className, $path=false) {
		$tagName = strtolower($tagName);
		static::$tagClass[$tagName] = $className;
		if ($path)
			static::$tagPath[$tagName] = Config::getAbsolutePath($path);
	}
	
	public static function addTagNamespace($namespace) {
		static::$tagNamespace[] = static::formatNamespace($namespace);
	}
	
	protected static function formatNamespace($namespace) {
		if ($namespace[0] !== '\\')
			$namespace = '\\' . $namespace;
		if ($namespace[strlen($namespace) - 1] !== '\\')
			$namespace .= '\\';
		return $namespace;
	}
	
	public static function getFilterClass($filterName, $origin=false) {
		return static::getModuleClass($filterName, 'Filter',
			static::$filterClass, static::$filterPath,
			static::$filterNamespace, $origin);
	}
	
	public static function getTagClass($tagName, $origin=false) {
		return static::getModuleClass($tagName, 'Tag',
			static::$tagClass, static::$tagPath,
			static::$tagNamespace, $origin);
	}
	
	protected static function getModuleClass($modName, $modType, &$modClasses,
			&$modPaths, &$modNamespaces, $origin=false) {
		$lowName = strtolower($modName);
		if (isset($modClasses[$lowName])) {
			if (isset($modPaths[$lowName]))
				require_once($modPaths[$lowName]);
			return $modClasses[$lowName];
		}
		$properName = ucfirst($lowName) . $modType;
		foreach ($modNamespaces as $namespace) {
			$class = $namespace . $properName;
			if (@class_exists($class)) {
				$modClasses[$lowName] = $class;
				return $class;
			}
		}
		$error = $modType . ' "' . $modName . '" does not exist' .
			($origin ? ' in template "' . $origin . '".' : '.');
		if ($modType === 'Filter')
			throw new NoSuchFilterException($error);
		else
			throw new NoSuchTagException($error);
	}

	public static function getPHP($templateName, $loader) {
		$parser = new Parser($templateName, $loader);
		$nodes = $parser->parse();
		return $nodes->render();
	}

}

?>