<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\purephp;

use hydrogen\view\TemplateEngine;

class PurephpEngine implements TemplateEngine {

	public static function getPHP($templateName, $loader) {
		return $loader->load($templateName);
	}

}

?>