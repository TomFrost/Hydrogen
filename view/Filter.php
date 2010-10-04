<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

interface Filter {
	public static function applyTo($string, $args, $context);
}

?>