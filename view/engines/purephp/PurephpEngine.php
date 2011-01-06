<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\purephp;

use hydrogen\view\TemplateEngine;

/**
 * The purephp engine is the simplest possible templating engine for Hydrogen:
 * the code is literally pure PHP code, and it uses the variables and methods
 * provided by the {@link \hydrogen\view\ViewSandbox} in order to generate
 * its output.
 *
 * Documentation and examples on this format can be found in the Hydrogen
 * Overview.
 *
 * @link http://www.webdevrefinery.com/forums/topic/1440-hydrogen-overview/
 *		Hydrogen Overview
 */
class PurephpEngine implements TemplateEngine {

	public static function getPHP($templateName, $loader) {
		return $loader->load($templateName);
	}

}

?>