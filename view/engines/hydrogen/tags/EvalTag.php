<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\tags;

use hydrogen\view\engines\hydrogen\Tag;
use hydrogen\view\engines\hydrogen\nodes\EvalNode;
use hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException;

class EvalTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
		if (empty($args))
			throw new TemplateSyntaxException("Tag 'eval' requires an expression argument in template $origin.");
		return new EvalNode($args, $origin);
	}

}

?>