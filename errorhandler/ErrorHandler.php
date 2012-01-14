<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\errorhandler;

use hydrogen\config\Config;
use hydrogen\errorhandler\ActionDescriptor;
use hydrogen\log\Log;

class ErrorHandler {
	// 2xx Successful
	const HTTP_OK								= "200 OK";
	const HTTP_CREATED							= "201 Created";
	const HTTP_ACCEPTED							= "202 Accepted";
	const HTTP_NONAUTHORITATIVE_INFORMATION		= "203 Non-Authoritative Information";
	const HTTP_NO_CONTENT						= "204 No Content";
	const HTTP_RESET_CONTENT					= "205 Reset Content";
	const HTTP_PARTIAL_CONTENT					= "206 Partial Content";
	
	// 3xx Redirection
	const HTTP_MULTIPLE_CHOICES					= "300 Multiple Choices";
	const HTTP_MOVED_PERMANENTLY				= "301 Moved Permanently";
	const HTTP_FOUND							= "302 Found";
	const HTTP_SEE_OTHER						= "303 See Other";
	const HTTP_NOT_MODIFIED						= "304 Not Modified";
	const HTTP_USE_PROXY						= "305 Use Proxy";
	const HTTP_TEMPORARY_REDIRECT				= "307 Temporary Redirect";
	
	// 4xx Client Error
	const HTTP_BAD_REQUEST						= "400 Bad Request";
	const HTTP_UNAUTHORIZED						= "401 Unauthorized";
	const HTTP_PAYMENT_REQUIRED					= "402 Payment Required";
	const HTTP_FORBIDDEN						= "403 Forbidden";
	const HTTP_NOT_FOUND						= "404 Not Found";
	const HTTP_METHOD_NOT_ALLOWED				= "405 Method Not Allowed";
	const HTTP_NOT_ACCEPTABLE					= "406 Not Acceptable";
	const HTTP_PROXY_AUTHENTICATION_REQUIRED	= "407 Proxy Authentication Required";
	const HTTP_REQUEST_TIMEOUT					= "408 Request Timeout";
	const HTTP_CONFLICT							= "409 Conflict";
	const HTTP_GONE								= "410 Gone";
	const HTTP_LENGTH_REQUIRED					= "411 Length Required";
	const HTTP_PRECONDITION_FAILED				= "412 Precondition Failed";
	const HTTP_REQUEST_ENTITY_TOO_LARGE			= "413 Request Entity Too Large";
	const HTTP_REQUEST_URI_TOO_LONG				= "414 Request-URI Too Long";
	const HTTP_UNSUPPORTED_MEDIA_TYPE			= "415 Unsupported Media Type";
	const HTTP_REQUESTED_RANGE_NOT_SATIFSIABLE	= "416 Requested Range Not Satisfiable";
	const HTTP_EXPECTATION_FAILED				= "417 Expectation Failed";
	
	// 5xx Server Error
	const HTTP_INTERNAL_SERVER_ERROR			= "500 Internal Server Error";
	const HTTP_NOT_IMPLEMENTED					= "501 Not Implemented";
	const HTTP_BAD_GATEWAY						= "502 Bad Gateway";
	const HTTP_SERVICE_UNAVAILABLE				= "503 Service Unavailable";
	const HTTP_GATEWAY_TIMEOUT					= "504 Gateway Timeout";
	const HTTP_HTTP_VERSION_NOT_SUPPORTED		= "505 HTTP Version Not Supported";
	
	
	protected static $handlerStack = array();
	protected static $handling = false;
	protected static $errorLevel = NULL;
	
	private function __construct() {}
	
	public static function attach($statusCode=false) {
		if (!$statusCode)
			$statusCode = self::HTTP_INTERNAL_SERVER_ERROR;
		static::$handlerStack[] = new ActionDescriptor(ActionDescriptor::ERRORTYPE_DEFAULT, $statusCode);
		static::init();
	}
	
	public static function attachErrorPage($errPage=false, $statusCode=false) {
		if (!$statusCode)
			$statusCode = self::HTTP_INTERNAL_SERVER_ERROR;
		if (!$errPage)
			$errPage = __DIR__ . "/pages/DefaultGeneric.php";
		static::$handlerStack[] = new ActionDescriptor(ActionDescriptor::ERRORTYPE_FILE, $statusCode, $errPage);
		static::init();
	}
	
	public static function attachErrorString($errStr, $statusCode=false) {
		if (!$statusCode)
			$statusCode = self::HTTP_INTERNAL_SERVER_ERROR;
		static::$handlerStack[] = new ActionDescriptor(ActionDescriptor::ERRORTYPE_STRING, $statusCode, $errStr);
		static::init();
	}
	
	public static function attachRedirect($url, $statusCode=false) {
		if (!$statusCode)
			$statusCode = self::HTTP_TEMPORARY_REDIRECT;
		static::$handlerStack[] = new ActionDescriptor(ActionDescriptor::ERRORTYPE_REDIRECT, $statusCode, $url);
		static::init();
	}
	
	public static function detach() {
		if (static::$handling) {
			if (count(static::$handlerStack) > 1)
				array_pop(static::$handlerStack);
			else
				static::detachAll();
			return true;
		}
		return false;
	}
	
	public static function detachAll() {
		if (static::$handling) {
			static::$handlerStack = array();
			restore_error_handler();
			restore_exception_handler();
			ob_end_flush();
			static::$handling = false;
		}
	}
	
	public static function setHandledErrors($errorLevel) {
		static::$errorLevel = $errorLevel;
		if (static::$handling) {
			restore_error_handler();
			set_error_handler("\hydrogen\errorhandler\ErrorHandler::handleError", static::$errorLevel);
		}
	}
	
	protected static function init() {
		if (!static::$handling) {
			if (static::$errorLevel === false)
				static::$errorLevel = E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING;
			set_error_handler("\hydrogen\errorhandler\ErrorHandler::handleError", static::$errorLevel);
			set_exception_handler("\hydrogen\errorhandler\ErrorHandler::handleException");
			ob_start("\hydrogen\errorhandler\ErrorHandler::printBuffer");
			static::$handling = true;
		}
	}
	
	public static function handleError($errno, $errstr, $errfile, $errline) {
		if (Config::getVal("errorhandler", "log_errors") == "1")
			Log::error($errstr, $errfile, $errline);
		ob_end_clean();
		$ad = static::$handlerStack[count(static::$handlerStack) - 1];
		static::sendHttpCodeHeader($ad->responseCode);
		switch ($ad->errorType) {
			case ActionDescriptor::ERRORTYPE_DEFAULT:
				die(static::constructErrorPage($ad->responseCode));
			case ActionDescriptor::ERRORTYPE_FILE:
				ob_start();
				$success = @include($ad->errorData);
				$content = ob_get_contents();
				ob_end_clean();
				die($content);
			case ActionDescriptor::ERRORTYPE_STRING:
				die($ad->errorData);
			case ActionDescriptor::ERRORTYPE_REDIRECT:
				die(header("Location: " . $ad->errorData));
		}
	}
	
	public static function handleException($e) {
		static::handleError($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
	}
	
	public static function printBuffer($page) {
		return $page;
	}
	
	public static function sendHttpCodeHeader($codeString, $httpVer='1.1') {
		header("HTTP/$httpVer " . $codeString);
	}
	
	protected static function constructErrorPage($errorCode) {
		$page  = "<html>\n";
		$page .= "\t<head>\n";
		$page .= "\t\t<title>$errorCode</title>\n";
		$page .= "\t</head>\n";
		$page .= "\t<body>\n";
		$page .= "\t\t<h1>$errorCode</h1>\n";
		$page .= "\t</body>\n";
		$page .= "</html>";
		return $page;
	}
}

?>