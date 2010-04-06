#Hydrogen
####PHP 5.3+ Performance and Compatibility Library
####[HydrogenPHP.com](http://www.hydrogenphp.com)

ChangeLog
---------

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