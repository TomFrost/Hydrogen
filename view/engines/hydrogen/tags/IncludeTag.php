<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tags;

use hydrogen\view\engines\hydrogen\Tag;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class IncludeTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
		if (empty($args)) {
			throw new TemplateSyntaxException(
				'Tag "include" in template "' . $origin .
				'" requires a template name as an argument.');
		}
		$parser->prependPage($args);
		return false;
	}

}

?>