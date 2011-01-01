<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen;

interface Filter {
	public static function applyTo($string, $args, &$escape, $phpfile);
}

?>