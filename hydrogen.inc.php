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

function load($namespace) {
	while ($namespace[0] === '\\')
		$namespace = substr($namespace, 1);
	if (strpos($namespace, __NAMESPACE__) === 0) {
		$path = __DIR__ . '/' . str_replace('\\', '/', substr($namespace,
			strlen(__NAMESPACE__) + 1)) . '.php';
		return include_once($path);
	}
	return false;
}

function loadPath($absPath) {
	return include_once($absPath);
}

spl_autoload_register(__NAMESPACE__ . '\load');
include(__DIR__ . DIRECTORY_SEPARATOR . 'hydrogen.autoconfig.php');

?>