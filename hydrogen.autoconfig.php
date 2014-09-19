<?php
/***
 ***  Copyright (c) 2009 - 2012, Frosted Design
 ***  All rights reserved.
 ***
 ***********************************************
 ***  Config autoloader sample
 ***  Copy this to hydrogen.autoconfig.php
 ***/
namespace hydrogen;

use hydrogen\config\Config;
use hydrogen\view\engines\hydrogen\HydrogenEngine;

/***  Set the base path for the application here.  This should not be the path
 ***  to Hydrogen, but rather, the "root" folder of this webapp.
 ***
 ***  This MUST be an absolute path.  You can use PHP's __DIR__ global to write
 ***  an absolute path that will allow your app to be moved or installed
 ***  anywhere without changing this value.  The following example assumes that
 ***  this autoconfig file is two levels down from the root of the app.
 ***/
Config::setBasePath(__DIR__ . "/../../..");


/***  Set the cache path for the application.  This directory should allow PHP
 ***  full read/write permissions, and any data that PHP must cache in a file
 ***  will be stored here.  Log files will also be stored here by default
 ***  unless the path is changed in the config.
 ***
 ***  If a relative path is given, it will be relative in relation to the base
 ***  path.  The following example assumes there is a fully-writable folder
 ***  called "cache" in the root of the webapp.
 ***/
Config::setCachePath("cache");


/***  This line loads the application's config file.
 ***
 ***  The first argument is the path to the config file itself.  This may be
 ***  absolute, or relative to the base path given above.
 ***
 ***  The second argument dictates how the config file is cached.  If true, the
 ***  processed config will be cached by its filename.  If it's a string, it
 ***  will be cached by that string.  If false, it won't be cached at all.  The
 ***  recommended value is "true" unless there's a chance you'll be loading
 ***  multiple config files with the same name.
 ***
 ***  If the third argument is true, it will check to see if the config file
 ***  has been modified every time the page is loaded, and, when a change is
 ***  detected, update the cached version of the file.  In production, this
 ***  value can be set to false to save CPU cycles and stat() calls.  To make
 ***  config changes take effect in this case, simply delete the cached config
 ***  file.
 ***/
Config::addConfig(
	'config/myapp.ini.php', // Config file path
	true, // Cache this config file?
	true // Check for config file changes before using cached version?
	);

	
/*#  The rest of this file can be used to override user-specified config
 *#  settings or to set new config items that shouldn't be presented to the
 *#  user.  What follows are the values Hydrogen needs from the programmer
 *#  (not the user -- user-specified values should all be in the config file
 *#  defind above).  Read through and set the appropriate values for each, but
 *#  feel free to add any other app-specific configuration values that the
 *#  user shouldn't change.
 */


/***  [view] -> engine
 ***  Default if not set: "hydrogen"
 ***
 ***  The view engine that should be used.  The default is "hydrogen", the
 ***  built-in super-fast django-style templating engine.  Uncomment this line
 ***  to change it to "purephp", which is raw PHP code for your views.
 ***/
//Config::setVal("view", "engine", "purephp");


/***  [view] -> loader
 ***  Default if not set: "File"
 ***
 ***  The view loader to be used.  Hydrogen can be extended to load templates
 ***  from anywhere; however, out of the box, the options are "File" and
 ***  "Database".  File, the default, will pull templates out of the main
 ***  filesystem.  Database requires that you have a table within the
 ***  configured Hydrogen database, with a column for the template name (such
 ***  as "artcle" or "account/login") and a column (probably of the TEXT type)
 ***  for the actual template content.  If you choose the Database loader, see
 ***  below to set these field/table names.
 ***/
//Config::setVal("view", "loader", "Database");


/***  FOR THE FILE LOADER ONLY:
 ***  [view] -> folder
 ***  This is a REQUIRED value (if using the File loader)
 ***
 ***  The folder, relative to the base path, where views for the View library
 ***  are stored.
 ***/
Config::setVal("view", "folder", "themes/default");


/***  FOR THE FILE LOADER ONLY:
 ***  [view] -> file_extension
 ***  This is a REQUIRED value (if using the File loader)
 ***
 ***  The extension of view filenames.
 ***/
Config::setVal("view", "file_extension", ".tpl.php");


/***  FOR THE DATABASE LOADER ONLY:
 ***  [view] -> table_name
 ***  This is a REQUIRED value (if using the Database loader)
 ***
 ***  The table name in your database from which templates will be pulled.
 ***/
//Config::setVal("view", "table_name", "templates");


/***  FOR THE DATABASE LOADER ONLY:
 ***  [view] -> name_field
 ***  This is a REQUIRED value (if using the Database loader)
 ***
 ***  The name of the column that contains the template name in the table
 ***  specified above.  The template name is the same as what would be used
 ***  with the file loader; so it could contain a simple name like "artcle" or
 ***  a path-style name like "account/login".  This field should probably be
 ***  a VARCHAR between 30 and 60 characters.
 ***/
//Config::setVal("view", "name_field", "name");


/***  FOR THE DATABASE LOADER ONLY:
 ***  [view] -> content_field
 ***  This is a REQUIRED value (if using the Database loader)
 ***
 ***  The name of the column that contains the template content in the table
 ***  specified above.  This field should probably be either TEXT or MEDIUMTEXT
 ***  unless the application has very specific template needs.  Note that this
 ***  field can contain templates for any template engine supported by
 ***  Hydrogen.
 ***/
//Config::setVal("view", "content_field", "content");


/***  [view] -> url_path
 ***  This is a REQUIRED value
 ***
 ***  The URL path to add to the general->app_url config value to target static
 ***  view files (images, css, etc) with the web browser.  If you're using the
 ***  'File' loader, this will almost always match your view folder path.
 ***/
Config::setVal("view", "url_path", "themes/default");


/***  [view] -> root_url
 ***  This is an OPTIONAL value
 ***
 ***  Alternatively, you can set an entirely new URL as the view root.  This is
 ***  only needed in special circumstances, such as if a CDN is being used to
 ***  distribute static files.  Do not set this unless you know you need it.
 ***  This value will not be used unless [view]->url_path above is set to
 ***  false.
 ***/
//Config::setVal("view", "root_url", "http://cloud.domain.com/theme/default");


/***  FOR THE 'hydrogen' VIEW ENGINE ONLY:
 ***  [view] -> print_missing_var
 ***  Default if not set: false
 ***
 ***  In a template, if a variable is requested but is missing, should we
 ***  print it out like this: {?varname?}  in the page output?  If false,
 ***  an exception will be thrown instead.  Leave this on for development, but
 ***  the default is false.
 ***/	
Config::setVal("view", "print_missing_var", true);


/***  FOR THE 'hydrogen' VIEW ENGINE ONLY:
 ***  [view] -> allow_php
 ***  Default if not set: false
 ***
 ***  If false, a TemplateSyntaxException will be thrown when raw PHP code is
 ***  used inside a Hydrogen template file.  Set to 'true' to allow templates
 ***  to contain raw PHP.  This is not recommended in environments where
 ***  third parties can submit template code.
 ***/	
//Config::setVal("view", "allow_php", true);


/***  FOR THE 'hydrogen' VIEW ENGINE ONLY:
 ***  [view] -> autoescape
 ***  Default if not set: true
 ***
 ***  By default, the Hydrogen Templating Engine will escape all variable
 ***  tags with the htmlentities() PHP method.  This value can be set to
 ***  false to turn that feature off.  Just be aware that each potential
 ***  output will need to be escaped manually if there's any risk of them
 ***  containing code!
 ***/
//Config::setVal("view", "autoescape", false);


/***  FOR THE 'hydrogen' VIEW ENGINE ONLY:
 ***  Custom filter declarations
 ***
 ***  The following methods can be used to add custom filters to the Hydrogen
 ***  templating engine without them physically existing in the Hydrogen
 ***  library folder or namespace.  The first method declares classes
 ***  individually (with optional file path if it won't be autoloaded), while
 ***  the second adds a namespace from which filters can be autoloaded.  See
 ***  the documentation in hydrogen/view/engines/hydrogen/HydrogenEngine.php
 ***  for details and usage.
 ***/
/*HydrogenEngine::addFilter('swedishchef', '\myapp\filters\BorkFilter',
	'lib/myapp/filters/BorkFilter.php'); */
//HydrogenEngine::addFilterNamespace('\myapp\filters');


/***  FOR THE 'hydrogen' VIEW ENGINE ONLY:
 ***  Custom tag declarations
 ***
 ***  The following methods can be used to add custom tags to the Hydrogen
 ***  templating engine without them physically existing in the Hydrogen
 ***  library folder or namespace.  The first method declares classes
 ***  individually (with optional file path if it won't be autoloaded), while
 ***  the second adds a namespace from which tags can be autoloaded.  See
 ***  the documentation in hydrogen/view/engines/hydrogen/HydrogenEngine.php
 ***  for details and usage.
 ***/
/*HydrogenEngine::addTag('onweekdays', '\myapp\tags\OnweekdaysTag',
	'lib/myapp/tags/OnweekdaysTag.php'); */
//HydrogenEngine::addTagNamespace('\myapp\tags');


/***  [view] -> use_cache
 ***  This is a REQUIRED value
 ***
 ***  This value controls the cacheing of compiled views.  This *dramatically*
 ***  increases the performance of your view rendering, shaving a considerable
 ***  amount execution time, RAM, and CPU usage off of each request.  If on,
 ***  each view will be loaded from a raw PHP file (opcode-cached if you're
 ***  using something like XCache or APC) inside of the cache folder defined
 ***  above.  However, any time a change is made to a view template, the cached
 ***  files will have to be manually deleted to cause Hydrogen to recompile the
 ***  view.  It is recommended to leave this off during development to avoid
 ***  that hassle, but keep it on in production.
 ***/
Config::setVal("view", "use_cache", false);

?>
