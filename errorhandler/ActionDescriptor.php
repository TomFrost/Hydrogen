<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\errorhandler;

class ActionDescriptor {
	const ERRORTYPE_DEFAULT = 0;
	const ERRORTYPE_FILE = 1;
	const ERRORTYPE_STRING = 2;
	const ERRORTYPE_REDIRECT = 3;
	
	public $errorType, $errorData, $responseCode;
	
	public function __construct($errorType, $responseCode, $errorData=false) {
		$this->errorType = $errorType;
		$this->errorData = $errorData;
		$this->responseCode = $responseCode;
	}
}

?>