#Hydrogen
####PHP 5.3+ Performance and Compatibility Library
####[HydrogenPHP.com](http://www.hydrogenphp.com)

ChangeLog
---------

### v0.2.5
- **Hydrogen**
	- Added 'common' library to store classes used across multiple Hydrogen libraries.
	- Important changes to the autoconfig file to support new Config library features.  Current Hydrogen users will need to update their autoconfigs to use the new sample template.

- **Common**
	- Created abstract class "MagicCacheable" making it easy to implement and hook Hydrogen's Magic Cache pattern across other classes.  Detailed documentation included.
	- Added NoSuchMethodException for use with MagicCacheable.
	- Added InvalidPathException, used by both Config and Log.

- **Config**
	- Changed ALL SAMPLE CONFIG FILES with completely new structure.  Current Hydrogen users MUST update their config files to be compatible with the new formats.
	- Rewrote config loader to support the new config file templates, allowing the use of multiple config files, cacheing with file-based semaphores instead of config file-controlled semaphores, and support for caching multiple like-named config files.
	- New Config library API to support cache path/base path getters and setters, addConfig instead of loadConfig (now possible to load additional configurations without replacing what was already loaded), replaceConfig (for when you do want to replace), and addConfigArray (for adding raw, already-processed config values)
	- New format for cached config files.  For people upgrading, Config will detect when an old cached config is present and update it with the new format automatically.  No code changes necessary.
	- InvalidPathException is now in the 'common' namespace.

- **Controller**
	- Rewritten to extend the new MagicCacheable class.
	- Controller now caches both the function output _and_ its return value.  This is helpful for controllers that call cached versions of other controllers.

- **Database**
	- Verb and method arrays in Query have been moved to the protected scope
	- Bugfix: Changed reference to PDO::execute() to PDO::exec() in PDOEngine.
	- Bugfix: InvalidSQLException had a typo and would generate an error instead of properly throwing the exception ([Issue #2](http://github.com/TomFrost/Hydrogen/issues/closed#issue/2)). Resolved.

- **ErrorHandler**
	- Bugfix: ErrorHandler was never updated to use the new getVal/getRequiredVal Config API changes, and as a result, would not log errors even when error logging was turned on.  Resolved.

- **Log**
	- Bugfix: InvalidPathException reference was invalid.  Log now throws the InvalidPathException added to the new Common namespace.

- **Model**
	- Rewritten to extend the new MagicCacheable class.

### v0.2.0
- **Hydrogen**
	- Drastically changed the autoconfig file.  See comments for new instructions on this file.
	- Added a function in the hydrogen namespace to load specific PHP files outside of a class context.
	- Paths have changed significantly.  The new basePath variable is the root from which all relative paths should be resolved -- including the cache folder path in the autoconfig.

- **Cache**
	- Added MemcachedEngine, using the libmemcached-powered Memcached PHP extension.
		- Supports connection pooling.  Setting the config value [cache]->pool_name enables this feature.
		- Note that, at the time of this writing, the Memcached extension has a bug preventing pooling to be used if libmemcached > 0.38 is installed.  If this is the case, simply disable pooling.
	- Fixed errors in MemcacheEngine.
		- Some versions of the Memcache extension did not properly delete keys.  The fixed engine resolves this issue.
		- set() now uses the actual set() function in the engine.  This was an add/replace scheme in the past due to a Memcache extension bug that no longer poses an issue.

- **Config**
	- Added a function to resolve relative paths in reference to the base path.
	- Added a function to detect whether or not a path is relative.
	- Split getVal into two functions:
		- getVal() will get the requested value and return false if it does not exist.
		- getRequiredVal() will get the requested value and throw an exception if it does not exist.
	- Added an argument to both getVal and getRequiredVal, allowing a subkey to be specified.  Any given key may now be changed to an array or associative array, and the proper index can be retrieved with this subkey argument.  See hydrogen/config/samples/config_multidb_sample.ini.php for illustration.

- **Controller**
	- Added initial version of the new library.
	- Controller class added.  See hydrogen/controller/Controller.php for documentation.
	- Dispatcher class added.  See hydrogen/controller/Dispatcher.php for documentation.

- **Database**
	- Significant changes to DatabaseEngineFactory to support multiple databases.
		- getCustomEngine() is now used to build a custom engine on the fly.
		- getEngine() is used to build an engine from values specified in Config.
		- getEngine() may be passed a variable to specify which database configuration to pull from Config.
	- DatabaseEngine now contains the table prefix setting, so each database connection, whether configured or made custom on-the-fly, may have an attached table prefix.  A function has been added to return this prefix for a particular engine.
	- Multiple databases may now be defined with a subkey.  See hydrogen/config/samples/config_multidb_sample.ini.php for illustration.  If using a single database, no config change is necessary.

- **ErrorHandler**
	- Added a function to send a specific error code header.
	
- **Log**
	- TextFileEngine's path in the config file is now relative to the base path in Hydrogen's autoconfig file.
	
- **Model**
	- Database dependency has been removed.  This assists in the new multi-database support, as well as encourages better model design.
	- Fixed bug causing the default cache time of 300 not to be used if not explicitly specified.

- **SQLBeans**
	- Fixed bug causing the update() method to not always use the appropriate database connection when multiple databases are in use.

- **View**
	- New library added!  See hydrogen/view/View.php for documentation.  This can be used completely independently of the new Controller class, or a different view/template library can be used in place.

### v0.1.2
- **Hydrogen**
	- Hydrogen now has an official license file!  See "LICENSE" for details.  It's now fully legal and free to use in most projects, and much more legit.  I retain the right to change this license in future releases.
	- The readme is now somewhat useful ;-).

- **Config**
	- Added Config::getConfigPath() method to get the last config file path passed to Config::loadConfig().

- **Database**
	- Fixed strict parsing error in try/catch blocks of PDOEngine.

- **ErrorHandler**
	- Corrected spelling of ErrorHandler::detatch() and ErrorHandler::detatchAll() methods.  They are not ErrorHandler::detach() and ErrorHandler::detachAll(), respectively.
	- Corrected logic error causing ErrorHandler::detach() not to detach properly when there was only one attached handler.

- **Log**
	- TextFileEngine
		- Now compatible with Windows!  Windows users can now make full use of Hydrogen's Log library.
		- When a relative path is given for the logfile in the Hydrogen config file, it is treated as relative from the location of the config file *as long as an absolute path is given for the config file in hydrogen.autoload.php*.
		- If a relative logfile path is used in the config file AND the config file path is relative in hydrogen.autoload.php, a hydrogen\log\exceptions\InvalidPathException will be thrown.
		- If the logfile can't be written to/isn't found/etc., the engine will throw an InvalidPathException instead of returning false.
		- This engine no longer sets a default timezone.  If an upgrade triggers a timezone error in PHP, be sure to set your timezone in your php.ini file.

- **Semaphore**
	- Fixed "NoEngine" so that it could be used without error.
	- Allowed "engine" config option to be left blank, defaulting to "No"

### v0.1.0
- **Released in Alpha**