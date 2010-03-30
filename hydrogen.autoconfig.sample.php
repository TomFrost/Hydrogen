<?php
/**
 * Config autoloader sample
 * Copy this to hydrogen.autoconfig.php
 */
namespace hydrogen;

use \hydrogen\config\Config;

/* 
 * Load the file with caching.  This sample assumes this directory structure:
 *
 * + cache
 * + config
 * +-- config.ini.php
 * + lib
 * +-- hydrogen
 * +-- +-- hydrogen.autoconfig.php
 *
 * Change this line to fit the paths you're using.
 */
Config::loadConfig(__DIR__ . '/../../config/config.ini.php', __DIR__ . '/../../cache', true);

?>
