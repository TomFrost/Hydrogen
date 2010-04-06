## Changelog for Hydrogen ##

## Alpha 2
Database
- Fixed strict parsing error in try/catch blocks of PDOEngine.

ErrorHandler
- Corrected spelling of ErrorHandler::detatch() and ErrorHandler::detatchAll() methods.  They are not ErrorHandler::detach() and ErrorHandler::detachAll(), respectively.
- Corrected logic error causing ErrorHandler::detach() not to detach properly when there was only one attached handler.

Semaphore
- Fixed "NoEngine" so that it could be used without error.
- Allowed "engine" config option to be left blank, defaulting to "No"

## Alpha 1
- Released!