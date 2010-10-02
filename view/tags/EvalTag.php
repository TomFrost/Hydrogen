<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\tags;

use hydrogen\view\Tag;
use hydrogen\view\nodes\EvalNode;
use hydrogen\view\exceptions\TemplateSyntaxException;

class EvalTag extends Tag {

	public static function getNode($cmd, $args, $parser, $origin) {
		if (empty($args))
			throw new TemplateSyntaxException("Tag 'eval' requires an expression argument in template $origin.");
		return new EvalNode($args);
	}

}

?>