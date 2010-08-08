<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

interface Tag {
	const MUST_BE_FIRST = false;
	
	public function getNode($origin, $data, $parser);
}

?>