<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

abstract class TemplateEngine {

	public abstract static function getPHP($templateName, $loader);
	
	// Engines themselves should not be instantiated
	protected function __construct() {}

}

?>