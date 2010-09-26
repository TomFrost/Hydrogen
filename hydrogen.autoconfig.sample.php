<?php
/**
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 *
 ********************************************
 * Config autoloader sample
 * Copy this to hydrogen.autoconfig.php
 */
namespace hydrogen;

use hydrogen\config\Config;

/*
 * Set the base path for the application here.  This should not be the path to
 * Hydrogen, but rather, the "root" folder of this webapp.
 *
 * This MUST be an absolute path.  You can use PHP's __DIR__ global to write an
 * absolute path that will allow your app to be moved or installed anywhere without
 * changing this value.  The following example assumes that this autoconfig file is
 * two levels down from the root of the app.
 */
Config::setBasePath(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "..");

/*
 * Set the cache path for the application.  This directory should allow PHP full
 * read/write permissions, and any data that PHP must cache in a file will be
 * stored here.  Log files will also be stored here by default unless the path
 * is changed in the config.
 *
 * If a relative path is given, it will be relative in relation to the base
 * path.  The following example assumes there is a fully-writable folder called
 * "cache" in the root of the webapp.
 */
Config::setCachePath("cache");

/* 
 * This line loads the application's config file.
 *
 * The first argument is the path to the config file itself.  This may be absolute,
 * or relative to the base path given above.
 *
 * The second argument dictates how the config file is cached.  If true, the
 * processed config will be cached by its filename.  If it's a string, it will
 * be cached by that string.  If false, it won't be cached at all.  The
 * recommended value is "true" unless there's a chance you'll be loading
 * multiple config files with the same name.
 *
 * If the third argument is true, it will check to see if the config file has been 
 * modified every time the page is loaded, and, when a change is detected, update the 
 * cached version of the file.  In production, this value can be set to false to save 
 * CPU cycles and stat() calls.  To make config changes take effect in this case, 
 * simply delete the cached config file.
 */
Config::addConfig(
	'config' . DIRECTORY_SEPARATOR . 'config.ini.php', // Config file path
	true, // Cache this config file?
	true // Check for config file changes before using cached version?
	);
	
/*
 * The rest of this file can be used to override user-specified config settings or
 * set config new items that shouldn't be presented to the user.
 */

// The folder, relative to the base path, where views for the View library are stored.
Config::setVal("view", "folder", "themes" . DIRECTORY_SEPARATOR . "default");

// The URL path to add to the general->app_url config value to target view files
// within the web browser.  This is usually the same as the folder above.
Config::setVal("view", "url_path", "themes/default");

// Alternatively, you can set an entirely new URL as the view root.  This is only
// needed in special circumstances.  Do not set this unless you know you need it.
//Config::setVal("view", "root_url", "http://cloud.domain.com/theme/default");

// The extension of view filenames.
Config::setVal("view", "file_extension", ".tpl.php");

// Should we be using the templating engine?
Config::setVal("view", "use_templates", "1");

// For templates, which loader should we use? (Hint: use "File" unless you are
// weird and intriguing.  In fact, you don't even have to -- it's the default!)
//Config::setVal("view", "loader_type", "File");

// In a template, if a variable is requested but is missing, should we
// print it out like this: ?? varname ??  in the page output?  If false,
// an exception will be thrown instead.  Leave this on for development, but
// the default is false.
Config::setVal("view", "print_missing_var", true);

?>
