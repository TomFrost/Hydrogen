<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

use hydrogen\config\Config;
use hydrogen\view\ContextStack;
use hydrogen\view\exceptions\NoSuchVariableException;
use hydrogen\view\exceptions\NoSuchViewException;

class ViewSandbox {
	
	protected $context;
	protected $cleanAppURL;
	protected $cleanViewURL;
	
	/**
	 * Creates a new ViewSandbox and calculates the necessary URLs.
	 *
	 * @param ContextStack|boolean context The context in which to load the
	 * 		view.  If false or omitted, an empty context will be used.
	 */
	public function __construct($context=false) {
		$this->context = $context ?: new ContextStack();
		$this->cleanAppURL = Config::getRequiredVal("general", "app_url");
		if ($this->cleanAppURL[strlen($this->cleanAppURL) - 1] == '/')
			$this->cleanAppURL = substr($this->cleanAppURL, 0, -1);
		$this->cleanViewURL = Config::getVal("view", "root_url");
		if ($this->cleanViewURL === false) {
			$this->cleanViewURL = $this->appURL(Config::getRequiredVal("view", 
				"url_path"));
		}
		if ($this->cleanViewURL[strlen($this->cleanViewURL) - 1] == '/')
			$this->cleanViewURL = substr($this->cleanViewURL, 0, -1);
		$this->context->viewURL = $this->cleanViewURL;
		$this->context->appURL = $this->cleanAppURL;
	}
	
	/**
	 * Returns request-scope variables as they are called upon.
	 *
	 * This is a PHP Magic Method and should not be called directly.
	 *
	 * @param varName string The name of the variable being asked for.
	 * @return mixed The value of the variable if it exists, or false if
	 * 		does not.
	 */
	public function __get($varName) {
		try {
			$val = $this->context->get($varName);
		}
		catch (NoSuchVariableException $e) {
			return false;
		}
		return $val;
	}
	
	/**
	 * Sets or creates a new request-scope variable.
	 *
	 * This is a PHP Magic Method and should not be called directly.
	 *
	 * @param varName string The name of the variable to set or create
	 * 		with the specified value.
	 * @param value mixed The value to which the variable should be set.
	 */
	public function __set($varName, $value) {
		$this->context->set($varName, $value);
	}
	
	/**
	 * Checks to see whether a specified request-scope variable exists.
	 *
	 * This is a PHP Magic Method and should not be called directly.
	 *
	 * @param varName string The name of the variable to check.
	 * @return boolean true if set, false if not set.
	 */
	public function __isset($varName) {
		return $this->context->keyExists($varName);
	}
	
	/**
	 * Unsets (deletes) the specified request-scope variable.
	 *
	 * This is a PHP Magic Method and should not be called directly.
	 *
	 * @param varName string The name of the variable to unset.
	 */
	public function __unset($varName) {
		$this->context->delete($varName);
	}
	
	/**
	 * Loads the specified view within this sandbox, making the current
	 * context available to it.
	 *
	 * @param viewName string The name of the view to display.
	 */
	public function loadView($viewName) {
		View::loadIntoSandbox($viewName, $this);
	}
	
	/**
	 * Generates a URL relative to the base URL of this web application.
	 * Calling this function with no arguments returns the base URL set in
	 * the config file, with the trailing slash (if there is one) removed.
	 *
	 * Optionally, this function may be called with a path, which will be
	 * appended to the base URL before it is returned.
	 *
	 * @param path string|boolean The path to append to the base app URL, or
	 * 		false to return the base URL with no additional path.
	 * @return string The base URL for this web app with the given path
	 * 		appended, if provided.
	 */
	public function appURL($path=false) {
		if ($path !== false) {
			if ($path[0] == '/')
				return $this->cleanAppURL . $path;
			return $this->cleanAppURL . '/' . $path;
		}
		return $this->cleanAppURL;
	}
	
	/**
	 * Generates a URL relative to the root view URL of this web application.
	 * Calling this function with no arguments returns the root view URL for
	 * the currently used view, with no trailing slash.  Calling this function
	 * with a path returns the root view URL with the given path appended
	 * to it.
	 *
	 * @param path string|boolean The path to append to the root view URL, or
	 * 		false to return the view URL with no additional path.
	 * @return string The root URL for this view with the given path appended,
	 * 		if provided.
	 */
	public function viewURL($path=false) {
		if ($path !== false) {
			if ($path[0] == '/')
				return $this->cleanViewURL . $path;
			return $this->cleanViewURL . '/' . $path;
		}
		return $this->cleanViewURL;
	}
	
	/**
	 * Executes the supplied PHP file inside the sandbox, making the variable
	 * $context (the ContextStack for this sandbox) available to it.  Note
	 * that the context is also available by accessing $this.
	 *
	 * @param string filePath The absolute path to the PHP file to be loaded.
	 * @throws NoSuchViewException if the PHP file could not be loaded.
	 */
	public function loadPHPFile($filePath) {
		$context = $this->context;
		$success = @include($filePath);
		if (!$success) {
			throw new NoSuchViewException("View file could not be loaded: " .
				$filePath);
		}
	}
	
	/**
	 * Executes the supplied PHP code inside the sandbox, making the variable
	 * $context (the ContextStack for this sandbox) available to it.  Note
	 * that the context is also available by accessing $this.
	 *
	 * @param string phpCode A string containing raw PHP code to be executed.
	 * @throws NoSuchViewException if the code contains a parse error and
	 * 		cannot be properly executed.
	 */
	public function loadRawPHP($phpCode) {
		$context = $this->context;
		echo $phpCode;
		$parsed = eval('?>' . $phpCode);
		if ($parsed === false)
			throw new NoSuchViewException("Raw PHP view could not be loaded due to parse error.");
	}
}

?>