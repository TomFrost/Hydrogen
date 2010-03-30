<?php
/**
 * Hydrogen loader.  require_once() this file from any php page that can
 * be loaded directly.  This file will autoload any other hydrogen classes
 * as they're used, so no others requires are necessary.
 */
namespace hydrogen;

function load($namespace) {
	$splitpath = explode('\\', $namespace);
	$path = '';
	$name = '';
	$firstword = true;
	for ($i = 0; $i < count($splitpath); $i++) {
		if ($splitpath[$i] && !$firstword) {
			if ($i == count($splitpath) - 1)
				$name = $splitpath[$i];
			else
				$path .= DIRECTORY_SEPARATOR . $splitpath[$i];
		}
		if ($splitpath[$i] && $firstword) {
			if ($splitpath[$i] != __NAMESPACE__)
				break;
			$firstword = false;
		}
	}
	if (!$firstword) {
		$fullpath = __DIR__ . $path . DIRECTORY_SEPARATOR . $name . '.php';
		return include($fullpath);
	}
	return false;
}

spl_autoload_register(__NAMESPACE__ . '\load');
include(__DIR__ . DIRECTORY_SEPARATOR . 'hydrogen.autoconfig.php');

?>