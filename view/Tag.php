<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

abstract class Tag {
	public abstract static function getNode($cmd, $args, $parser, $origin);
	
	public static function mustBeFirst() {
		return false;
	}
}

?>