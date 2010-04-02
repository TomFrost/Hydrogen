<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\recache;

class AssetWrapper {
	public $time, $expire, $groups, $data;
	
	function __construct($data, $expire=false, $groups=false) {
		$this->time = round(microtime(true), 4);
		if ($expire === 0)
			$expire = false;
		else if ($expire)
			$expire = $this->time + $expire;
		$this->expire = $expire;
		if ($groups === false || is_null($groups))
			$groups = array();
		else if (!is_array($groups))
			$groups = array($groups);
		$this->groups = $groups;
		$this->data = $data;
	}
}

?>