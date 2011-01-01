<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 *
 *************************************************************************
 * Hydrogen loader.  require_once() this file from any php page that can
 * be loaded directly.  This file will autoload any other hydrogen classes
 * as they're used, so no others requires are necessary.
 */
namespace hydrogen;

use hydrogen\common\exceptions\ClassFileNotFoundException;

function load($namespace) {
	while ($namespace[0] === '\\')
		$namespace = substr($namespace, 1);
	if (strpos($namespace, __NAMESPACE__) === 0) {
		$path = __DIR__ . '/' . str_replace('\\', '/', substr($namespace,
			strlen(__NAMESPACE__) + 1)) . '.php';
		set_error_handler('\hydrogen\fileNotFoundHandler', E_WARNING);
		try {
			include_once($path);
		}
		catch (ClassFileNotFoundException $e) {
			restore_error_handler();
			return false;
		}
		restore_error_handler();
		return true;
	}
	return false;
}

function loadPath($absPath) {
	return include_once($absPath);
}

function fileNotFoundHandler($errno, $errstr, $errfile, $errline) {
	if (preg_match('/^include_once.*' . __NAMESPACE__ .
			'.*failed to open stream/', $errstr)) {
		throw new ClassFileNotFoundException();
	}
	else {
		$caller = debug_backtrace();
		$caller = $caller[1];
		trigger_error($errstr . ' in <strong>' . $caller['function'] .
			'</strong> called from <strong>' . $caller['file'] . 
			'</strong> on line <strong>' . $caller['line'] .
			"</strong>\n<br />error handler", E_USER_WARNING);
	}
}

spl_autoload_register(__NAMESPACE__ . '\load');
include(__DIR__ . DIRECTORY_SEPARATOR . 'hydrogen.autoconfig.php');

?>