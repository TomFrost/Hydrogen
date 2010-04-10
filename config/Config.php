<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\config;

use hydrogen\config\exceptions\ConfigKeyNotFoundException;
use hydrogen\config\exceptions\ConfigFileNotDefinedException;
use hydrogen\config\exceptions\ConfigFileNotFoundException;
use hydrogen\config\exceptions\InvalidConfigFileException;
use hydrogen\config\exceptions\ConfigCacheDirNotWritableException;
use hydrogen\config\exceptions\ConfigCacheDirNotFoundException;
use hydrogen\semaphore\SemaphoreEngineFactory;

/**
 * The Config class offers a project-wide app configuration solution.  It can be used
 * standalone to store and retrieve values during the lifetime of the request, or read
 * in a configuration file in various formats.
 * 
 * While the Config object is intended to hold the configuration necessary for the
 * Hydrogen packages to function, it can be used to manage the configuration for
 * any aspect of the project, all within the same configuration file if desired.
 * 
 * Sample configuration files can be found in in the hydrogen/config/sample folder.
 * Available options are as follows:
 * <ul>
 * <li> Preferred: A special PHP-INI formatted file that appears as an INI format,
 * 		but is valid PHP and will not be output to the browser when requested.
 * 		(see config_sample.ini.php)
 * <li> An INI-formatted file (see config_sample.ini)
 * <li> A PHP file that outputs INI-formatted text.
 * <li> A pure PHP file that sets configuration options through an array
 * 		or a series of calls to {@link #setVal} (see config_sample.php).
 * </ul>
 * 
 * Note that the INI and PHP-INI formats are parsed with PHP's built-in native
 * parser, and has all its inherent capabilities.  Additionally, it is far faster
 * than manual parsing.  To dissuade any fears about processing speed, though,
 * The Config object has a built-in caching system to automatically output the
 * configuration file as executable PHP, populating the Config object with a single
 * method call.  More importantly, as a pure qualified PHP file, PHP opcode cachers
 * will cache the entire file and load it from memory when it's requested.
 * 
 * If {@link compareModifiedDates} is set to <code>false</code>, Config effectively
 * generates no stat calls in order to load its config options.  For every connection,
 * then, the hard drive is not touched and no database is accessed for the
 * configuration.  This makes Hydrogen's Config an extremely lightweight, high-traffic
 * config solution.
 */
class Config {
	protected static $values = array();
	protected static $configPath = false;
	
	/**
	 * Retrieves a specified value from the currently loaded configuration.
	 *
	 * @param string section The section name under which to look for the key.
	 * @param string key The key name for which to retrieve the value.
	 * @param string | boolean subkey The subkey name for wich to retreive the value.
	 *		<code>false</code> assumes there is no subkey and value is string
	 * @param boolean exceptionOnError <code>true</code> to have this method throw
	 * 		a {@link ConfigKeyNotFoundException} if the config file is not found;
	 * 		<code>false</code> to return <code>false</code> instead.
	 * @return The value of the requested section/key, or <code>false</code> if the
	 * 		specified section/key pair does not exist and <code>false</code> was specified
	 * 		for exceptionOnError.
	 * @throws ConfigKeyNotFoundException if the requested section/key pair does not
	 * 		exist, and exceptionOnError is set to <code>true</code>.
	 */
	public static function getVal($section, $key, $subkey=false, $exceptionOnError=false) {
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
			if (isset($trace[1])) {
				$call = $trace[1]['class'] . $trace[1]['type'] . $trace[1]['function'];
				$msg .= " Value is required by $call";
			}
			throw new ConfigKeyNotFoundException($msg, $call);
		}
		return false;
	}
	
	/**
	 * Retrieves a specified required value from the currently loaded configuration.
	 * This is equivalent to calling {@link #getVal} with a final argument of true.
	 *
	 * @param string section The section name under which to look for the key.
	 * @param string key The key name for which to retrieve the value.
	 * @param string | boolean subkey The subkey name for wich to retreive the value.
	 *		<code>false</code> assumes there is no subkey and value is string
	 * @return The value of the requested section/key.
	 * @throws ConfigKeyNotFoundException if the requested section/key pair does not
	 * 		exist.
	 */
	public static function getRequiredVal($section, $key, $subkey=false) {
		return static::getVal($section, $key, $subkey, true);
	}
	
	/**
	 * Loads the specified configuration file with or without caching.
	 *
	 * @param string configFile The full path to the config file to be loaded.  This file
	 *		can be any of the types listed within the class documentation.
	 * 		<strong>Recommended:</strong> use an absolute path to avoid unnecessary
	 * 		filesystem stats.
	 * @param string cacheDir The directory in which to save the cached configuration.
	 * 		This directory must be fully writable by PHP. <strong>Recommended:</strong>
	 * 		use an absolute path to avoid unnecessary filesystem stats.  Set to
	 * 		<code>false</code> or omit to disable caching.
	 * @param boolean compareDates Specifies whether or not the "Last Modified" dates on
	 * 		the real config file and cached config should be compared before the cached
	 * 		file is read.  If <code>true</code>, any change to the main config file will
	 * 		take effect immediately, but will cost two filesystem stat calls per request.
	 * 		If <code>false</code> no stat calls are generated, but the cache file must be
	 * 		manually deleted for config changes to take effect.
	 * @throws ConfigFileNotDefinedException if an empty or non-string path is provided
	 * 		for the config filename.
	 * @throws ConfigFileNotFoundException if the specified config file does not exist.
	 * @throws InvalidConfigFileException if the config file's formatting is corrupted or
	 * 		otherwise unreadable.
	 * @throws ConfigCacheDirNotFoundException if the specified cache directory does not exist.
	 * @throws ConfigCacheDirNotWritableException if PHP does not have write permissions for
	 * 		the specified cache directory.
	 */
	public static function loadConfig($configFile, $cacheDir=false, $compareDates=true) {
		if (!is_string($configFile) || ($configFile = trim($configFile)) == "")
			throw new ConfigFileNotDefinedException('Config file must be an actual legal file path.');
		static::$configPath = $configFile;
		$loadOrig = true;
		if ($cacheDir) {
			$loadOrig = false;
			$cachePath = $cacheDir . DIRECTORY_SEPARATOR . 'hydrogen' .
					DIRECTORY_SEPARATOR . 'config';
			$cacheFile = 'config.quickload.php';
			$fullPath = $cachePath . DIRECTORY_SEPARATOR . $cacheFile;
			if ($compareDates) {
				$origTime = @filemtime($configFile);
				if (!$origTime)
					throw new ConfigFileNotFoundException('Config file not found.');
				$cacheTime = @filemtime($fullPath);
				if (!$cacheTime || ($origTime > $cacheTime))
					$loadOrig = true;
			}
			if (!$loadOrig) {
				$loaded = @include($fullPath);
				if (!$loaded || count(static::$values) == 0)
					$loadOrig = true;
			}
		}
		if ($loadOrig) {
			ob_start();
			$loaded = @include($configFile);
			$content = ob_get_contents();
			ob_end_clean();
			if (!$loaded)
				throw new ConfigFileNotFoundException('Config file not found.');
			if (class_exists('\hydrogen\config\ConfigINI'))
				$content = &\hydrogen\config\ConfigINI::$ini;
			if ($content) {
				$values = parse_ini_string($content, true);
				if (!$values)
					throw new InvalidConfigFileException('Config file format is invalid and could not be read.');
				static::$values = $values;
			}
			if (count(static::$values) == 0)
				throw new InvalidConfigFileException('Config file format is invalid or empty and could not be read.');
			if ($cacheDir) {
				$sem = SemaphoreEngineFactory::getEngine();
				$key = 'config_cache';
				if ($sem->acquire($key, 0)) {
					if (!file_exists($cacheDir)) {
						$sem->release($key);
						throw new ConfigCacheDirNotFoundException('The cache directory does not exist.');
					}
					if (!file_exists($cachePath)) {
						$success = @mkdir($cachePath, 0777, true);
						if (!$success) {
							$sem->release($key);
							throw new ConfigCacheDirNotWritableException('Could not create a new directory ' .
								'within the cache directory.');
						}
					}
					$fp = @fopen($fullPath, 'w');
					if (!$fp) {
						$sem->release($key);
						throw new ConfigCacheDirNotWritableException('Could not create or open the config ' .
							'cache file for writing.');
					}
					$success = @fwrite($fp, static::exportAsPHP());
					if (!$success) {
						$sem->release($key);
						throw new ConfigCacheDirNotWritableException('Could not write to config cache file.');
					}
					@fclose($fp);
					$sem->release($key);
				}
			}
		}
	}
	
	/**
	 * Gets the last config file path that {@link #loadConfig} was called with.  Note that this is
	 * not guaranteed to be an absolute path, and the path is not guaranteed to have contained
	 * a successfully parsed configuration file.
	 *
	 * @return The last known config file path, or false if {@link #loadConfig} has not yet been
	 * 		called.
	 */
	public static function getConfigPath() {
		return static::$configPath;
	}
	
	/**
	 * Exports the currently loaded configuration (including loaded files as well as manual changes)
	 * in executable PHP format, suitable for saving in a .php file to be loaded later.  The saved
	 * file consists of a single {@link #setConfigArray} call, with the argument being all the
	 * config items parsed into an array.
	 *
	 * @return An executable PHP block in string form.
	 */
	protected static function exportAsPHP() {
		$out = "<?php\n";
		$out .= '\\' . get_called_class() . '::setConfigArray(';
		$out .= static::arrayToPHPString(static::$values);
		$out .= ");\n?>";
		return $out;
	}
	
	/**
	 * Exports any array as fully qualified PHP.
	 *
	 * @param array array The array to be parsed into a PHP string.  Note that
	 * 		this function does not support objects in arrays.  All objects should
	 * 		be serialized before this method is called.
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
			$str .= "\n" . static::tabs($numTabs) . $skey . " => " . $sval. ",";
		}
		return substr($str, 0, -1) . "\n" . static::tabs($numTabs) . ")";
	}
	
	/**
	 * Creates the specified number of tabs.
	 *
	 * @param int num The number of tabs to create.
	 * @return string A string with the specified number of tabs.
	 */
	protected static function tabs($num) {
		$tabs = '';
		for ($i = 0; $i < $num; $i++)
			$tabs .= "\t";
		return $tabs;
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
	 * Forcibly replaces the current configuration with a given associative array
	 * of configuration values of the following format:
	 * <pre>
	 * array(
	 * 		"section1" => array(
	 * 			"key1" => "value1",
	 * 			"key2" => "value2"
	 * 		),
	 * 		"section2" => array(
	 * 			"key1" => "value1",
	 * 			"key2" => "value2"
	 * 		)
	 * );
	 * </pre>
	 *
	 * @param array array The associative array with which to replace the current
	 * 		configuration.
	 */
	public static function setConfigArray($array) {
		static::$values = $array;
	}
	
	/**
	 * This class should never be instantiated.
	 */
	private function __construct() {}
}

?>