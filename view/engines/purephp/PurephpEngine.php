<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\purephp;

use hydrogen\view\TemplateEngine;

class PurephpEngine extends TemplateEngine {

	public static function getPHP($templateName, $loader) {
		return "<?php echo 'unfinished!'; ?>";
	}

}

?>