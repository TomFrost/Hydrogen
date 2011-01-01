<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\config;

use hydrogen\config\exceptions\ConfigKeyNotFoundException;
use hydrogen\config\exceptions\ConfigFileNotDefinedException;
use hydrogen\config\exceptions\ConfigFileNotFoundException;
use hydrogen\config\exceptions\InvalidConfigFileException;
use hydrogen\config\exceptions\ConfigCacheDirNotWritableException;
use hydrogen\config\exceptions\ConfigCacheDirNotFoundException;
use hydrogen\common\exceptions\InvalidPathException;
use hydrogen\semaphore\SemaphoreEngineFactory;

/**
 * The Config class offers a project-wide app configuration solution.  It can be
 * used standalone to store and retrieve values during the lifetime of the
 * request, or read in a configuration file in various formats.
 * 
 * While the Config object is intended to hold the configuration necessary for
 * the Hydrogen packages to function, it can be used to manage the configuration
 * for any aspect of the project, all within the same configuration file if
 * desired.
 * 
 * Sample configuration files can be found in in the hydrogen/config/sample
 * folder.  Available options are as follows:
 * <ul>
 * <li> Preferred: A special PHP-INI formatted file that appears as an INI
 * 		format, but is valid PHP and will not be output to the browser when
 * 		requested. (see config_sample.ini.php)
 * <li> An INI-formatted file (see config_sample.ini)
 * <li> A PHP file that outputs INI-formatted text.
 * <li> A pure PHP file that sets configuration options through an array
 * 		or a series of calls to {@link #setVal} (see config_sample.php).
 * </ul>
 * 
 * Note that the INI and PHP-INI formats are parsed with PHP's built-in native
 * parser, and has all its inherent capabilities.  Additionally, it is far
 * faster than manual parsing.  To dissuade any fears about processing speed,
 * though, the Config object has a built-in caching system to automatically
 * output the configuration file as executable PHP, populating the Config object
 * with a single method call.  More importantly, as a pure qualified PHP file,
 * PHP opcode cachers will cache the entire file and load it from memory when
 * it's requested.
 * 
 * If the compareDates paramater of {@link #loadConfig} is set to
 * <code>false</code>, Config effectively generates no stat calls in order to
 * load its config options.  For every connection, then, the hard drive is not
 * touched and no database is accessed for the configuration.  This makes
 * Hydrogen's Config an extremely lightweight, high-traffic config solution.
 */
class Config {
	protected static $values = array();
	protected static $basePath = false;
	protected static $cachePath = false;
	
	/**
	 * Sets the base path of this webapp.  This configuration value is not
	 * accessed or changed with the rest of the config options, because core
	 * configuration functions depend on this value.
	 *
	 * This function should be called before any part of Hydrogen is interacted
	 * with.  Normally, it is called as the very first instruction in 
	 * hydrogen.autoconfig.php.
	 *
	 * @param basePath string The absolute path to the root of this webapp.
	 * @throws InvalidPathException if the provided path is relative.
	 */
	public static function setBasePath($basePath) {
		if (static::isRelativePath($basePath))
			throw new InvalidPathException("Base path must be absolute.");
		
		// Remove trailing slash(es)
		while ($basePath{strlen($basePath) - 1} === DIRECTORY_SEPARATOR)
			$basePath = substr($basePath, 0, strlen($basePath) - 1);
		
		static::$basePath = $basePath;
	}
	
	/**
	 * Sets the cache path of this webapp.  The folder specified should allow
	 * the PHP-running user full read/write access to it and its contents.
	 * The supplied path may be absolute or relative.  If relative, the path
	 * will be resolved relative to the base path if it exists.
	 *
	 * This configuration value is not accessed or changed with the rest of the
	 * config options, because core configuration functions depend on this
	 * value.  This function should be called before any config file is loaded.
	 * Normally, this will be the second method called in
	 * hydrogen.autoconfig.php, right after {@link #setBasePath}.
	 *
	 * @param cachePath The absolute or relative path to a folder for which
	 * 		PHP has full read and write permissions.
	 * @throws InvalidPathException if the provided path is relative and
	 * 		{@link #setBasePath} has not been successfully called.
	 */
	public static function setCachePath($cachePath) {
		$cachePath = static::getAbsolutePath($cachePath);
		
		// Remove trailing slash(es)
		while ($cachePath{strlen($cachePath) - 1} === DIRECTORY_SEPARATOR)
			$cachePath = substr($cachePath, 0, strlen($cachePath) - 1);
			
		static::$cachePath = $cachePath;
	}
	
	/**
	 * Gets the base path of this webapp, set by the {@link #setBasePath}
	 * function.
	 *
	 * @return The base path, or false if {@link #setBasePath} has not yet been
	 * 		called.  The base path will not have a trailing slash.
	 */
	public static function getBasePath() {
		return static::$basePath;
	}
	
	/**
	 * Gets the cache path of this webapp, set by the {@link #setCachePath}
	 * function.
	 *
	 * @return The cache path, or false if {@link #setCachePath} has not yet
	 * 		been called.  The cache path will not have a trailing slash.
	 */
	public static function getCachePath() {
		return static::$cachePath;
	}
	
	/**
	 * Retrieves a specified value from the currently loaded configuration.
	 *
	 * @param string section The section name under which to look for the key.
	 * @param string key The key name for which to retrieve the value.
	 * @param string | boolean subkey The subkey name for which to retrieve the
	 * 		value.  <code>false</code> assumes there is no subkey.
	 * @param boolean exceptionOnError <code>true</code> to have this method
	 * 		throw a {@link ConfigKeyNotFoundException} if the config file is not
	 * 		found; <code>false</code> to return <code>false</code> instead.
	 * @return The value of the requested section/key, or <code>false</code> if
	 * 		the specified section/key pair does not exist and <code>false</code>
	 * 		was specified for exceptionOnError.
	 * @throws ConfigKeyNotFoundException if the requested section/key pair does
	 * 		not exist, and exceptionOnError is set to <code>true</code>.
	 */
	public static function getVal($section, $key, $subkey=false,
			$exceptionOnError=false) {
		if ($subkey === false && isset(static::$values[$section][$key]))
			return static::$values[$section][$key];
		else if ($subkey && isset(static::$values[$section][$key][$subkey]))
			return static::$values[$section][$key][$subkey];
		
		if ($exceptionOnError) {
			$msg = "Config value not found: [$section][$key]";
			if ($subkey === true)
				$msg .= "[$subkey]";
			$msg .= ".";
			$trace = debug_backtrace();
			$call = false;
			$i = 1;
			while ((!$call ||
					$call === 'hydrogen\config\Config::getRequiredVal') &&
					isset($trace[$i])) {
				$call = $trace[$i]['class'] . $trace[$i]['type'] .
					$trace[$i]['function'];
				$i++;
			}
			if ($call)
				$msg .= " Value is required by $call";
			throw new ConfigKeyNotFoundException($msg, $call);
		}
		return false;
	}
	
	/**
	 * Retrieves a specified required value from the currently loaded
	 * configuration.  This is equivalent to calling {@link #getVal} with a
	 * final argument of true.
	 *
	 * @param string section The section name under which to look for the key.
	 * @param string key The key name for which to retrieve the value.
	 * @param string | boolean subkey The subkey name for which to retrieve the
	 * 		value. <code>false</code> assumes there is no subkey.
	 * @return The value of the requested section/key.
	 * @throws ConfigKeyNotFoundException if the requested section/key pair does
	 * 		not exist.
	 */
	public static function getRequiredVal($section, $key, $subkey=false) {
		return static::getVal($section, $key, $subkey, true);
	}
	
	/**
	 * Loads the specified config file with or without caching, and adds the
	 * configuration values to the ones already loaded (if any).
	 *
	 * @param string configFile The path, absolute or relative, to the config
	 * 		file to be loaded.  If the path is relative, it will be resolved
	 * 		relative to the base path set with {@link #setBasePath} method.
	 * @param boolean overwriteChanges If true, any keys in the new config that
	 * 		have already been loaded in previous configs will be overwritten
	 * 		with the new values.  If false, the existing values will take
	 * 		precedence.
	 * @param boolean|string cacheKey The name to cache the parsed config file
	 * 		under.  If true, the given filename will be used.  If false, no
	 * 		caching will occur.  Otherwise, a string can be passed in to
	 * 		determine the cache key.  IMPORTANT: The cache key will be used AS
	 * 		THE FILENAME, so it must only contain characters legal in filenames.
	 * @param boolean compareDates Specifies whether or not the "Last Modified"
	 * 		dates on the real config file and cached config should be compared
	 * 		before the cached file is read.  If <code>true</code>, any change to
	 * 		the main config file will take effect immediately, but will cost two
	 * 		filesystem stat calls per request. If <code>false</code> no stat
	 * 		calls are generated, but the cache file must be manually deleted for
	 * 		config changes to take effect.
	 */
	public static function addConfig($configFile, $overwriteChanges=true,
			$cacheKey=true, $compareDates=true) {
		$newConfig = static::loadConfig($configFile, $cacheKey, $compareDates);
		if ($overwriteChanges)
			static::$values = array_merge(static::$values, $newConfig);
		else
			static::$values = array_merge($newConfig, static::$values);
	}
	
	/**
	 * Merges the supplied config value array with the one currently loaded
	 * into the Config class (if any).
	 *
	 * @param array configArray An multidimensional associative array of
	 * 		grouped configuration values.
	 * @param boolean overwriteChanges If true, any keys in the new config that
	 * 		have already been loaded in previous configs will be overwritten
	 * 		with the new values.  If false, the existing values will take
	 * 		precedence.
	 */
	public static function addConfigArray($configArray,
			$overwriteChanges=true) {
		if ($overwriteChanges)
			static::$values = array_merge(static::$values, $configArray);
		else
			static::$values = array_merge($configArray, static::$values);
	}
	
	/**
	 * Replaces all set config values (if any) with the contents of the config
	 * file specified.  The specified file can be read directly or cached.
	 *
	 * @param string configFile The path, absolute or relative, to the config
	 * 		file to be loaded.  If the path is relative, it will be resolved
	 * 		relative to the base path set with {@link #setBasePath} method.
	 * @param boolean|string cacheKey The name to cache the parsed config file
	 * 		under.  If true, the given filename will be used.  If false, no
	 * 		caching will occur.  Otherwise, a string can be passed in to
	 * 		determine the cache key.  IMPORTANT: The cache key will be used AS
	 * 		THE FILENAME, so it must only contain characters legal in filenames.
	 * @param boolean compareDates Specifies whether or not the "Last Modified"
	 * 		dates on the real config file and cached config should be compared
	 * 		before the cached file is read.  If <code>true</code>, any change to
	 * 		the main config file will take effect immediately, but will cost two
	 * 		filesystem stat calls per request. If <code>false</code> no stat
	 * 		calls are generated, but the cache file must be manually deleted for
	 * 		config changes to take effect.
	 */
	public static function replaceConfig($configFile, $cacheKey=true,
			$compareDates=true) {
		static::$values = static::loadConfig($configFile, $cacheKey,
			$compareDates);
	}
	
	/**
	 * Replaces all set config values (if any) with the contents of the of the
	 * one supplied.
	 *
	 * @param array configArray An multidimensional associative array of
	 * 		grouped configuration values.
	 */
	public static function replaceConfigArray($configArray) {
		static::$values = $configArray;
	}
	
	/**
	 * Loads the specified configuration file with or without caching, and
	 * returns the parsed values as an associative array.  In order to use
	 * caching, {@link #setCacheDir} must have been called with a valid writable
	 * folder path.
	 *
	 * @param string configFile The path to the config file to be loaded. This
	 * 		file can be any of the types listed within the class documentation.
	 * 		If a relative path is given, the path will be resolve in relation
	 * 		to the base path set with the {@link #setBasePath} function.
	 * @param boolean|string cacheKey The name to cache the parsed config file
	 * 		under.  If true, the given filename will be used.  If false, no
	 * 		caching will occur.  Otherwise, a string can be passed in to
	 * 		determine the cache key.  IMPORTANT: The cache key will be used AS
	 * 		THE FILENAME, so it must only contain characters legal in filenames.
	 * @param boolean compareDates Specifies whether or not the "Last Modified"
	 * 		dates on the real config file and cached config should be compared
	 * 		before the cached file is read.  If <code>true</code>, any change to
	 * 		the main config file will take effect immediately, but will cost two
	 * 		filesystem stat calls per request. If <code>false</code> no stat
	 * 		calls are generated, but the cache file must be manually deleted for
	 * 		config changes to take effect.
	 * @return array The parsed config values drawn from the config file path or
	 * 		cached version of the file.
	 * @throws ConfigFileNotDefinedException if an empty or non-string path is
	 * 		provided for the config filename.
	 * @throws ConfigFileNotFoundException if the specified config file does not
	 * 		exist.
	 * @throws InvalidConfigFileException if the config file's formatting is
	 * 		corrupted or otherwise unreadable.
	 * @throws ConfigCacheDirNotFoundException if the cache directory set with
	 * 		{@link #setCacheDir} does not exist.
	 * @throws ConfigCacheDirNotWritableException if PHP does not have write
	 * 		permissions for the specified cache directory.
	 */
	protected static function loadConfig($configFile, $cacheKey=true,
			$compareDates=true) {
		if (!is_string($configFile) || ($configFile = trim($configFile)) == "")
			throw new ConfigFileNotDefinedException('Config file must be an actual legal file path.');
		$configFile = static::getAbsolutePath($configFile);
		
		// Shall we attempt to load a cached version?
		if ($cacheKey && static::$cachePath) {
			$cacheValid = true;
			$configCachePath = static::$cachePath .
				DIRECTORY_SEPARATOR . 'hydrogen' .
				DIRECTORY_SEPARATOR . 'config';
			$cacheFile = basename($cacheKey === true ? $configFile : $cacheKey,
				".php") . ".php";
			$fullPath = $configCachePath . DIRECTORY_SEPARATOR . $cacheFile;
			if ($compareDates) {
				$origTime = @filemtime($configFile);
				if (!$origTime)
					throw new ConfigFileNotFoundException('Config file not found.');
				$cacheTime = @filemtime($fullPath);
				if ($cacheTime && ($origTime > $cacheTime))
					$cacheValid = false;
			}
			if ($cacheValid) {
				$loaded = @include($fullPath);
				if ($loaded && isset($cachedConfig))
					return $cachedConfig;
			}
		}
		
		// We're not loading the cached version -- load the original.
		ob_start();
		$loaded = @include($configFile);
		$content = ob_get_contents();
		ob_end_clean();
		if (!$loaded)
			throw new ConfigFileNotFoundException('Config file not found.');
		if (isset($ini))
			$content = &$ini;
		if ($content) {
			$values = parse_ini_string($content, true);
			if (!$values)
				throw new InvalidConfigFileException('Config file format is invalid and could not be read.');
		}
		if (isset($configArray) && is_array($configArray))
			$values = &$configArray;
			
		// The original config is loaded.  Should we cache it?
		if ($cacheKey && static::$cachePath) {
			if (!file_exists(static::$cachePath))
				throw new ConfigCacheDirNotFoundException('The cache directory does not exist.');
			if (!file_exists($configCachePath)) {
				$success = @mkdir($configCachePath, 0777, true);
				if (!$success)
					throw new ConfigCacheDirNotWritableException('Could not create a new directory within the cache directory.');
			}
			$fp = @fopen($fullPath, 'w');
			if (!$fp)
				throw new ConfigCacheDirNotWritableException('Could not create or open the config cache file for writing.');
			// Get the lock on the file.  If we can't get the lock, bypass all
			// this and return the loaded config.
			if (@flock($fp, LOCK_EX | LOCAL_NB)) {
				$success = @fwrite($fp, static::exportAsPHP($values,
					"cachedConfig"));
				if (!$success) {
					@fclose($fp);
					throw new ConfigCacheDirNotWritableException('Could not write to config cache file.');
				}
			}
			@fclose($fp);
		}
		
		// Return the loaded config
		return $values;
	}
	
	/**
	 * Converts relative paths to absolute paths by treating the base path as
	 * the starting point for relative paths.
	 *
	 * Absolute paths are given WITHOUT using stat()-expensive functions like
	 * realpath().  The benefit is that absolute paths are generated very
	 * quickly and the target file/folder does not have to exist.  However, the
	 * path returned may contain double-dots (i.e. /home/name/www/../config)
	 * which is acceptable as an absolute path to most PHP functions.  This
	 * function can be forced to resolve the path without double-dots if
	 * necessary.
	 *
	 * If an absolute path is given, the same absolute path will be returned
	 * unaltered (unless removeDots is set to true, in which case it will be
	 * cleaned before being returned).
	 *
	 * This function works for both UNIX-based filesystems and Windows-based
	 * filesystems.
	 *
	 * @param path string The path to be made absolute in reference to the base
	 * 		path.
	 * @param removeDots boolean true to remove single dots from the resulting
	 * 		path and to remove double-dots (and their preceding directories) as
	 * 		well.  false to allow these artifacts to remain.  PHP accepts these
	 * 		paths as absolute, so unless absolutely necessary, this argument
	 * 		should be omitted or kept false.
	 * @return string An absolute path relative to the base path.  If an
	 * 		absolute path was supplied, it will not be transformed at all unless
	 * 		removeDots was set to true.
	 * @throws InvalidPathException if {@link #setBasePath} has not been called
	 * 		before this function.
	 */
	public static function getAbsolutePath($path, $removeDots=false) {
		if (static::isRelativePath($path)) {
			if (static::$basePath === false)
				throw new InvalidPathException("The config file path has not been set.  An absolute path cannot be generated.");
			$path = static::$basePath . DIRECTORY_SEPARATOR . $path;
		}
		if ($removeDots) {
			$singleDot = DIRECTORY_SEPARATOR . '.' . DIRECTORY_SEPARATOR;
			do {
				$path = str_replace($singleDot, DIRECTORY_SEPARATOR, $path,
					$count);
			} while ($count !== 0);
			$doubleSlash = DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR;
			do {
				$path = str_replace($doubleSlash, DIRECTORY_SEPARATOR, $path,
					$count);
			} while ($count !== 0);
			$safeSep = DIRECTORY_SEPARATOR;
			if (DIRECTORY_SEPARATOR === '\\')
				$safeSep = '\\\\';
			else if (DIRECTORY_SEPARATOR === '/')
				$safeSep = '\\/';
			$doubleDot = $safeSep . "[^$safeSep]+" . $safeSep . "\\.\\." .
				$safeSep;
			do {
				$path = preg_replace('/' . $doubleDot . '/',
					DIRECTORY_SEPARATOR, $path, -1, $count);
			} while ($count !== 0);
		}
		return $path;
	}
	
	/**
	 * Tests to see if the given path is relative.
	 *
	 * @param path string The path to be tested.
	 * @return true if the path is relative; false otherwise.
	 */
	protected static function isRelativePath($path) {
		if (DIRECTORY_SEPARATOR === '\\') {
			// Windows filesystem-specific
			$char = (int)$path[0];
			if ((($char >= (int)'a' && $char <= (int)'z') ||
				($char >= (int)'A' && $char <= (int)'Z')) &&
				$path[1] === ':' && $path[2] === DIRECTORY_SEPARATOR)
				return false;
		}
		if ($path[0] === DIRECTORY_SEPARATOR)
			return false;
		return true;
	}
	
	/**
	 * Exports the supplied array in executable PHP format, suitable for saving
	 * in a .php file to be loaded later.  The saved file consists of a single
	 * variable, with the value being all the key/value pairs in the given
	 * array.
	 *
	 * @param array array The PHP array to be exported as PHP code.
	 * @param string varName The name of the variable to assign the array
	 * 		to in the exported PHP code.
	 * @return An executable PHP block in string form.
	 */
	protected static function exportAsPHP($array, $varName) {
		$out = "<?php\n";
		$out .= '$' . $varName . ' = ';
		$out .= static::arrayToPHPString($array);
		$out .= ";\n?>";
		return $out;
	}
	
	/**
	 * Exports any array as fully qualified PHP.
	 *
	 * @param array array The array to be parsed into a PHP string.  Note that
	 * 		this function does not support objects in arrays.  All objects
	 * 		should be serialized before this method is called.
	 * @param int numTabs The number of tabs with which to precede each line.
	 * @return A string of PHP representing the given array.
	 */
	protected static function arrayToPHPString($array, $numTabs=1) {
		$str = "array(";
		foreach ($array as $key => $val) {
			if ($key === ((int)$key))
				$skey = $key;
			else
				$skey = "'" . str_replace("'", "\\'", $key) . "'";
			if (is_numeric($val))
				$sval = $val;
			else if (is_array($val))
				$sval = static::arrayToPHPString($val, $numTabs + 1);
			else
				$sval = "'" . str_replace("'", "\\'", $val) . "'";
			$str .= "\n" . str_repeat("\t", $numTabs) . $skey . " => " .
				$sval . ",";
		}
		return substr($str, 0, -1) . "\n" . str_repeat("\t", $numTabs) . ")";
	}
	
	/**
	 * Sets a certain section/key pair to the given value.
	 *
	 * @param string section The section name under which to find the key.
	 * @param string key The key name for which to set the value.
	 * @param mixed value The value for the specified key.
	 */
	public static function setVal($section, $key, $value) {
		static::$values[$section][$key] = $value;
	}
	
	/**
	 * This class should never be instantiated.
	 */
	private function __construct() {}
}

?>
