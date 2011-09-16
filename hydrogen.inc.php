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

// Common classes get loaded explicitly, because it's faster.
require_once(__DIR__ . '/config/Config.php');
require_once(__DIR__ . '/autoloader/Autoloader.php');

// All other classes are loaded through the autoloader.
use hydrogen\autoloader\Autoloader;
Autoloader::registerNamespace('hydrogen', __DIR__, false);
Autoloader::register();

// Run the autoconfig
require(defined('HYDROGEN_AUTOCONFIG_PATH') ?
	HYDROGEN_AUTOCONFIG_PATH :
	__DIR__ . '/hydrogen.autoconfig.php');

?>