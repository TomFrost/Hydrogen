<?php
hydrogen\config\Config::setConfigArray(array(
	'general' => array(
		'app_url' => 'http://example.com/app/folder'
		),
	'cache' => array(
		'engine' => 'Memcache'
		),
	'database' => array(
		'engine' => 'MysqlPDO',
		'host' => 'localhost',
		'port' => 3306,
		'database' => 'hydrogen',
		'username' => 'hydrogen',
		'password' => 'password',
		'table_prefix' => 'hydro_'
		),
	'recache' => array(
		'unique_name' => 'XYZ'
		),
	'semaphore' => array(
		'engine' => 'Cache'
		),
	'log' => array(
		'engine' => 'TextFile',
		'logdir' => 'cache',
		'fileprefix' => 'hydro_',
		'loglevel' => 1
		)
	));
?>