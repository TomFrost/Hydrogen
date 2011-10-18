<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\controller;

use hydrogen\config\Config;
use hydrogen\recache\RECacheManager;
use hydrogen\common\exceptions\NoSuchMethodException;
use hydrogen\controller\exceptions\MissingArgumentException;
use hydrogen\controller\exceptions\RouteSyntaxException;

/**
 * Router is the preferred method of matching URL requests to Controller
 * objects, making agressive use of caching to stay very quick, regardless of
 * the number or complexity of rules defined.  Many rules can be added to a
 * Router instance without incurring any extra processing or lookup time.
 *
 * Each "rule" in Router is simply a test to see if the request's URL
 * (specifically, the PATH_INFO) matches a specific pattern, and if so,
 * calling the appropriate Controller class and function to handle that
 * request.  Router rules can match all request types, or can be set to respond
 * only to DELETE, POST, PUT, or GET HTTP requests.
 *
 * The main goal of any routing rule is to end up with a variable named
 * 'controller' that defines which class should be loaded, and a variable
 * named 'function' that defines which function to call within the Controller
 * class.  This is a simple rule to direct requests to the root of the app
 * ('/') to the welcome Controller's greeting() function:
 *
 * <pre>
 * $router = new Router();
 * $router->request('/', array(
 *     'controller' => 'welcome',
 *     'function' => 'greeting'
 * ));
 * $router->start();
 * </pre>
 *
 * The supplied array of variables are the "defaults".  These can easily be
 * overridden by specifying that these variables should come from the URL:
 *
 * <pre>
 * $router->request('/:controller/:function');
 * </pre>
 *
 * The :variable format intercepts that specific piece of the request URL and
 * assigns it to the name of the variable.  In the example above, the Router
 * rule will match any URL with two path "segments" (like /welcome/greeting)
 * but ONLY if the specified controller and function actually exist.  If not,
 * Router will move on to the next rule.
 *
 * Parts of a URL can be made optional by putting them in parentheses.  The
 * following rule will match a direct request to the root, or allow the URL to
 * override the default controller and function:
 *
 * <pre>
 * $router->request('/(:controller(/:function))', array(
 *     'controller' => 'welcome',
 *     'function' => 'greeting'
 * ));
 * </pre>
 *
 * With the above rule, a request to /login would look for a Controller class
 * named 'login' and call its 'greeting' function.
 *
 * Consider, though, the liklihood that the controller classes are namespaced
 * and probably start with a capital first letter.  To achieve this, overrides
 * can be used.  Just as variables in URLs take precedence over the 'defaults'
 * array, overrides are applied before any controller function is called.
 *
 * <pre>
 * $router->request('/(:controller(/:function))', array(
 *     'controller' => 'welcome',
 *     'function' => 'greeting'
 * ), array(
 *     'controller' => '\myapp\controllers\%{controller|ucfirst}Controller'
 * ));
 * </pre>
 *
 * Using a second array of overrides, it's easy to transform a value of
 * 'welcome' for the controller into '\myapp\controllers\WelcomeController'.
 * In the array of overrides, existing variables can be referenced with
 * %variable or %{variable}.  Aditionally, it can be appended with a list of
 * pipe-separated filters to affect a change on the variable contents.  Legal
 * filters:
 *
 * - "ucfirst" makes the first letter uppercase
 * - "upper" makes the entire string uppercase
 * - "lower" will lowercase the entire string.
 *
 * Filters are executed in the order they are listed.  The following override
 * array will turn wELcOMe into Welcome:
 *
 * <pre>
 * array('controller' => '%{controller|lower|ucfirst});
 * </pre>
 *
 * Additional arguments aside from 'controller' and 'function' can also be
 * collected, and sent into the Controller's function as parameters.  The
 * following rule allows an optional "id" variable to be collected:
 *
 * <pre>
 * $router->request('/blog/:function(/:id)', array(
 *     'controller' => '\myapp\controllers\BlogController',
 *     'id' => 1
 * ));
 * </pre>
 *
 * With the above rule, if /blog/page/4 were requested, Router would call
 *
 * <pre>
 * \myapp\controllers\BlogController::page('4');
 * </pre>
 *
 * --assuming, of course, that BlogController contains a function called
 * 'page'.  Otherwise, Router would try any following rules.
 *
 * Groups of variables can also be captured by using the :*variable format.
 * Consider the following rule:
 *
 * <pre>
 * $router->request('/(:controller(/:function(/:*args)))', array(
 *     'controller' => 'home',
 *     'function' => 'index'
 * ), array(
 *     'controller' => '\myapp\controllers\%{controller|ucfirst}Controller',
 * ));
 * </pre>
 *
 * In the above case, if /blog/archive/2011/sept/10 is requested, Router would
 * make the following call:
 *
 * <pre>
 * \myapp\controllers\BlogController::archive("2011/sept/10");
 * </pre>
 *
 * It's unlikely that the argument would be useful without being separated into
 * each of its pieces, though, so Router provides two special overrides for
 * this case.  Overriding 'args' with <b>Router::EXPAND_ARRAY</b> would cause
 * this call to be made:
 *
 * <pre>
 * \myapp\controllers\BlogController::archive(array('2011', 'sept', '10'));
 * </pre>
 *
 * Or, override args with <b>Router::EXPAND_PARAMS</b> for this call:
 *
 * <pre>
 * \myapp\controllers\BlogController::archive('2011', 'sept', '10');
 * </pre>
 *
 * Router also allows each rule to specify a set of restrictions for each
 * variable in order for it to find a match, as well as controlling in which
 * order variables are sent to the function and whether or not all variables
 * are sent individually or passed in as a single associative array.  For
 * features not listed in this description, see the documentation for each
 * individual function.
 */
class Router {
	
	/**
	 * When caching is turned on, this specifies how long both rules and
	 * individual routes are cached by default.
	 */
	const DEFAULT_CACHE_TIME = 1800;
	
	/**
	 * Indicates the name of the variable that determines which controller
	 * is called.
	 */
	const KEYWORD_CONTROLLER = 'controller';
	
	/**
	 * Indicates the name of the variable that determines which function
	 * is called.
	 */
	const KEYWORD_FUNCTION = 'function';
	
	/**
	 * This constant can be used as an override to a wildcard variable.  It
	 * will cause each slash-separated element of the variable to be sent to
	 * the called function in an array.
	 */
	const EXPAND_ARRAY = '%{1}';
	
	/**
	 * This constant can be used as an override to a wildcard variable.  It
	 * will cause each slash-separated element of the variable to be sent to
	 * the called function as separate parameters.
	 */
	const EXPAND_PARAMS = '%{2}';
	
	/**
	 * @var array An array of overrides that applies to all rules made after
	 * this value is set.
	 */
	protected $globalOverrides;
	
	/**
	 * @var array The array of parsed and prepared routing tules.
	 */
	protected $ruleSet = array();
	
	/**
	 * @var boolean Indicates whether the current set of rules has been pulled
	 * directly from the cache.
	 */
	protected $rulesFromCache = false;
	
	/**
	 * @var string The name of this rule set.  This value is used for caching
	 * purposes only.
	 */
	protected $name;
	
	/**
	 * @var int The number of seconds before any cached piece of data
	 * associated with this Router instance should expire.
	 */
	protected $expireTime;
	
	/**
	 * @var array An array of RECache groups that should be linked to any
	 * cached piece of Router-associated data.
	 */
	protected $groups;
	
	/**
	 * @var boolean Indicates whether or not the full list of parsed rules
	 * should be pulled out of the cash on construction, or saved back into
	 * it when {@link #start} is called.
	 */
	protected $doRuleCache;
	
	/**
	 * @var boolean Indicates whether individually matched routes should be
	 * cached, avoiding running through the rule-matching logic for common
	 * requests.
	 */
	protected $doRouteCache;
	
	/**
	 * Creates a new Router object.  By default, the Router will automatically
	 * cache rule sets and matched routes.  To change this functionality,
	 * simply set [router]->cache_rules and [router]->cache_routes to true or
	 * false in the {@link \hydrogen\config\Config} object.
	 *
	 * @param string $name The name of the router.  This is used for caching
	 * 		purposes only.
	 * @param int $expireTime The number of seconds before cached rules and
	 * 		routes should expire, or 0 for no expiration.  The default is the
	 * 		value set for {@link DEFAULT_CACHE_TIME}.
	 * @param array $groups An array of groups that cached Router information
	 * 		should be associated with.  This is useful to automatically expire
	 * 		all cached routes should the code change.
	 */
	public function __construct($name=null, $expireTime=null, $groups=null) {
		$this->name = $name ?: '';
		$this->expireTime = $expireTime;
		$this->groups = $groups;
		$this->doRuleCache = Config::getVal('router', 'cache_rules');
		if ($this->doRuleCache === null)
			$this->doRuleCache = true;
		$this->doRouteCache = Config::getVal('router', 'cache_routes');
		if ($this->doRouteCache === null)
			$this->doRouteCache = true;
		
		// Attempt to get a cached version of this rule set
		if ($this->doRuleCache) {
			$cm = RECacheManager::getInstance();
			$rules = $cm->get("Router:" . $this->name, false);
			if ($rules) {
				$this->ruleSet = &$rules;
				$this->rulesFromCache = true;
			}
		}
	}
	
	/**
	 * Iterates through and applies an array of overrides to a given
	 * associative array of variables, while also noting which variables
	 * have been transformed with the {@link EXPAND_PARAMS} constant.
	 *
	 * Each of the variables supplied to this function are passed by reference,
	 * meaning the array of variables and the array tracking EXPAND_PARAMS will
	 * be modified in place.  This function does not return any value.
	 *
	 * @param array $vars An associative array of variables on which the
	 * 		overrides should be applied.
	 * @param array $overrides An associative array linking variables to
	 * 		overrides strings to apply to them.
	 * @param array $arraysAsParams An array denoting which overrides result in
	 * 		an array that should be expanded per the EXPAND_PARAMS constant.
	 * 		Any variable this applies to will be set as a key to a 'true'
	 * 		boolean value.
	 */
	protected function applyOverrides(&$vars, &$overrides, &$arraysAsParams) {
		foreach ($overrides as $var => $val) {
			if (is_array($val)) {
				// Construct a value from the array elements
				$newVal = '';
				foreach ($val as $elem) {
					if (is_array($elem)) {
						$varName = '';
						if (isset($vars[$elem[0]])) {
							$varName = $vars[array_shift($elem)];
							foreach ($elem as $filter) {
								switch ($filter) {
								case 'ucfirst':
									$varName = ucfirst($varName);
									break;
								case 'upper':
									$varName = strtoupper($varName);
									break;
								case 'lower':
									$varName = strtolower($varName);
									break;
								default:
									throw new RouteSyntaxException(
										"Filter '$filter' does not exist.");
								}
							}
						}
						$newVal .= $varName;
					}
					else
						$newVal .= $elem;
				}
				$vars[$var] = $newVal;
			}
			else {
				switch ($val) {
					case self::EXPAND_PARAMS:
						$arraysAsParams[$var] = true;
					case self::EXPAND_ARRAY:
						if (isset($vars[$var]))
							$vars[$var] = explode('/', $vars[$var]);
						break;
					default:
						$vars[$var] = $val;
				}
			}
		}
	}
	
	/**
	 * Inserts a new catch-all rule into the rule set.  Because a catch-all
	 * rule will always match the request URL when reached, it is useless to
	 * set any more rules after this function is called.
	 *
	 * @param array $defaults An associative array linking variable names to
	 * 		the value they should have when this rule matches.
	 * @param array $overrides An associative array linking variable names to
	 * 		what they should be set to after this rule matches and all other
	 * 		variables are processed.  See the documentation for
	 * 		{@link \hydrogen\controller\Router} for more information.
	 * @param array $argOrder An array indicating the order that the variables
	 * 		should have when passed to the controller function.
	 * @param boolean $argsAsArray true to call the controller function with
	 * 		a single argument: an associative array with a key/value pair
	 * 		or each variable. false (default) to pass each variable as a
	 * 		separate parameter.  Note that, if true, any variable transformed
	 * 		with {@link EXPAND_PARAMS} will instead be processed as though it
	 * 		were transformed with {@link EXPAND_ARRAY}.
	 * @return true if the rule was successfully added; false if the rule has
	 * 		already been loaded from the cached set.
	 */
	public function catchAll($defaults, $overrides=array(), $argOrder=null,
			$argsAsArray=false) {
		// Early exit if we already have the rules set up
		if ($this->rulesFromCache)
			return false;
		// Construct the catch-all rule
		$this->ruleSet[] = array(
			'method' => null,
			'regex' => '`.*`',
			'defaults' => $defaults,
			'overrides' => $this->processOverrides($overrides),
			'args' => $argOrder,
			'argArray' => !!$argsAsArray
		);
		return true;
	}
	
	/**
	 * Shortcut to calling {@link #request} with the DELETE http method.
	 *
	 * @param string $path The path pattern to match to the request.  See
	 * 		the {@link \hydrogen\controller\Router} for more information.
	 * @param array $defaults An associative array linking variable names to
	 * 		the default value they should have if they are not overridden by
	 * 		a section of the URL.
	 * @param array $overrides An associative array linking variable names to
	 * 		what they should be set to after this rule matches and all other
	 * 		variables are processed.  See the documentation for
	 * 		{@link \hydrogen\controller\Router} for more information.
	 * @param array $restrictions An associative array linking variables in the
	 * 		URL to a regex pattern that those variables must match in order for
	 * 		the rule as a whole to match.  Alternatively, the value can be an
	 * 		array of legal words for that particular variable instead of a
	 * 		regex string.
	 * @param array $argOrder An array indicating the order that the variables
	 * 		should have when passed to the controller function.
	 * @param boolean $argsAsArray true to call the controller function with
	 * 		a single argument: an associative array with a key/value pair
	 * 		or each variable. false (default) to pass each variable as a
	 * 		separate parameter.  Note that, if true, any variable transformed
	 * 		with {@link EXPAND_PARAMS} will instead be processed as though it
	 * 		were transformed with {@link EXPAND_ARRAY}.
	 * @return true if the rule was successfully added; false if the rule has
	 * 		already been loaded from the cached set.
	 */
	public function delete($path, $defaults=null, $overrides=null,
			$restrictions=null, $argOrder=null, $argsAsArray=false) {
		return request($path, $defaults, $overrides, $restrictions,
			$argOrder, $argsAsArray, 'DELETE');
	}
	
	/**
	 * Shortcut to calling {@link #request} with the GET http method.
	 *
	 * @param string $path The path pattern to match to the request.  See
	 * 		the {@link \hydrogen\controller\Router} for more information.
	 * @param array $defaults An associative array linking variable names to
	 * 		the default value they should have if they are not overridden by
	 * 		a section of the URL.
	 * @param array $overrides An associative array linking variable names to
	 * 		what they should be set to after this rule matches and all other
	 * 		variables are processed.  See the documentation for
	 * 		{@link \hydrogen\controller\Router} for more information.
	 * @param array $restrictions An associative array linking variables in the
	 * 		URL to a regex pattern that those variables must match in order for
	 * 		the rule as a whole to match.  Alternatively, the value can be an
	 * 		array of legal words for that particular variable instead of a
	 * 		regex string.
	 * @param array $argOrder An array indicating the order that the variables
	 * 		should have when passed to the controller function.
	 * @param boolean $argsAsArray true to call the controller function with
	 * 		a single argument: an associative array with a key/value pair
	 * 		or each variable. false (default) to pass each variable as a
	 * 		separate parameter.  Note that, if true, any variable transformed
	 * 		with {@link EXPAND_PARAMS} will instead be processed as though it
	 * 		were transformed with {@link EXPAND_ARRAY}.
	 * @return true if the rule was successfully added; false if the rule has
	 * 		already been loaded from the cached set.
	 */
	public function get($path, $defaults=null, $overrides=null,
			$restrictions=null, $argOrder=null, $argsAsArray=false) {
		return request($path, $defaults, $overrides, $restrictions,
			$argOrder, $argsAsArray, 'GET');
	}
	
	/**
	 * The handler used by the argument protection system in
	 * {@link #passRequest}.  Even though this argument is public for PHP
	 * error handling purposes, it should never be called directly.
	 *
	 * This error handler throws a {@link MissingArgumentException} whenever
	 * the error is, in fact, for a missing argument.  Otherwise, the handler
	 * re-throws the error as an E_USER_WARNING.
	 *
	 * @param int $errno The error type number.
	 * @param string $errstr A string describing the error.
	 * @param string $errfile The filename in which the error occurred.
	 * @param int $errline The line number on which the error occurred.
	 */
	public function missingArgHandler($errno, $errstr, $errfile,
			$errline) {
		$errCheck = "Missing argument";
		if ($errCheck === substr($errstr, 0, strlen($errCheck)))
			throw new MissingArgumentException();
		else {
			$caller = debug_backtrace();
			$caller = $caller[1];
			trigger_error($errstr . ' in <strong>' . $caller['function'] .
				'</strong> called from <strong>' . $caller['file'] . 
				'</strong> on line <strong>' . $caller['line'] .
				"</strong>\n<br />error handler", E_USER_WARNING);
		}
	}
	
	/**
	 * Attempts to pass the current page request to a specified controller,
	 * calling a function with a list of arguments.
	 *
	 * @param string $controller The class name of the controller to which the
	 * 		request should be passed.  This can either be a fully qualified
	 * 		class name with a namespace, or a simple name that can have
	 * 		a namespace prepended and a suffix appended to it later.
	 * @param string $function The name of the function inside of the controller
	 * 		to be called.
	 * @param array $args An array of arguments to be passed to the
	 * 		specified function, in order; null for no arguments.
	 * @param boolean $argProtection true to have this function return false if
	 * 		the specified function has more required arguments than what was
	 * 		included in the args array.  If false, this protection will be
	 * 		turned off and PHP's usual warning when a function with missing
	 * 		parameters is called will be fired.  Note that, if this is true,
	 * 		any warnings that are generated naturally by PHP will come as an
	 * 		E_USER_WARNING rather than an E_WARNING, due to limitations in
	 * 		PHP's error system.
	 * @return boolean true if the request was dispatched successfully,
	 * 		false otherwise.
	 */
	protected function passRequest($controller, $function, $args=null, 
			$argProtection=null) {
		// Only proceed if the controller exists
		if (@class_exists($controller)) {
			// Call it, Cap'n.
			$inst = $controller::getInstance();
			
			try {
				call_user_func_array(array($inst, $function), $args ?: array());
			}
			catch (NoSuchMethodException $e) {
				if ($argProtection)
					restore_error_handler();
				return false;
			}
			catch (MissingArgumentException $e) {
				if ($argProtection)
					restore_error_handler();
				return false;
			}
			if ($argProtection)
				restore_error_handler();
			return true;
		}
		return false;
	}
	
	/**
	 * Shortcut to calling {@link #request} with the POST http method.
	 *
	 * @param string $path The path pattern to match to the request.  See
	 * 		the {@link \hydrogen\controller\Router} for more information.
	 * @param array $defaults An associative array linking variable names to
	 * 		the default value they should have if they are not overridden by
	 * 		a section of the URL.
	 * @param array $overrides An associative array linking variable names to
	 * 		what they should be set to after this rule matches and all other
	 * 		variables are processed.  See the documentation for
	 * 		{@link \hydrogen\controller\Router} for more information.
	 * @param array $restrictions An associative array linking variables in the
	 * 		URL to a regex pattern that those variables must match in order for
	 * 		the rule as a whole to match.  Alternatively, the value can be an
	 * 		array of legal words for that particular variable instead of a
	 * 		regex string.
	 * @param array $argOrder An array indicating the order that the variables
	 * 		should have when passed to the controller function.
	 * @param boolean $argsAsArray true to call the controller function with
	 * 		a single argument: an associative array with a key/value pair
	 * 		or each variable. false (default) to pass each variable as a
	 * 		separate parameter.  Note that, if true, any variable transformed
	 * 		with {@link EXPAND_PARAMS} will instead be processed as though it
	 * 		were transformed with {@link EXPAND_ARRAY}.
	 * @return true if the rule was successfully added; false if the rule has
	 * 		already been loaded from the cached set.
	 */
	public function post($path, $defaults=null, $overrides=null,
			$restrictions=null, $argOrder=null, $argsAsArray=false) {
		return request($path, $defaults, $overrides, $restrictions,
			$argOrder, $argsAsArray, 'POST');
	}
	
	protected function processOverrides($overrides) {
		if (!$overrides)
			$overrides = array();
		if ($this->globalOverrides)
			$overrides = array_merge($this->globalOverrides, $overrides);
		// Iterate through overrides and break them up into segments
		foreach ($overrides as $var => $val) {
			// Break the string into variables and literals
			$splitRegex = '`(%(?:(?:[a-zA-Z_][a-zA-Z0-9_\|]*)|{(?:[a-zA-Z_][a-zA-Z0-9_\|]*)}))`';
			$tokens = preg_split($splitRegex, $val, null,
				PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			$set = array();
			// Iterate through each token to convert variables into arrays.
			// String literals stay as they are.
			foreach ($tokens as $segment) {
				if (preg_match($splitRegex, $segment)) {
					// This segment is a variable; put it in an array and add
					// any filters as additional array elements
					$segment = trim($segment, '%{}');
					if (strlen($segment)) {
						$segment = explode('|', $segment);
						$varBlock = array(array_shift($segment));
						foreach ($segment as $filter) {
							// Only allow legal filters
							switch ($filter) {
								case 'ucfirst':
								case 'upper':
								case 'lower':
									$varBlock[] = $filter;
									break;
								default:
									throw new RouteSyntaxException(
										"Illegal override filter: '$filter'");
							}
						}
						// Add the array package to the set
						$set[] = $varBlock;
					}
				}
				else
					$set[] = $segment;
			}
			// If our set only contains a single string literal, save it
			// that way so that only variables are arrays.
			$overrides[$var] = count($set) == 1 && !is_array($set[0]) ?
				$set[0] : $set;
		}
		return $overrides;
	}
	
	protected function processPath($path, $restrictions=null, &$args=null) {
		// Turn the parentheses into non-capturing optional groups.
		$path = str_replace('(', '(?:', $path, $openParens);
		$path = str_replace(')', ')?', $path, $closeParens);
		if ($openParens !== $closeParens) {
			throw new RouterSyntaxException(
				"Unequal number of closing and opening parentheses in '" .
				$path . "'.");
		}
		// Collect our variables
		preg_match_all('`(?<!\(\?):(?!(?:' . self::KEYWORD_CONTROLLER .
			'|' . self::KEYWORD_FUNCTION .
			')(?:/|\(|$))\*?([a-zA-Z_][a-zA-Z0-9_]*)`', $path, $args);
		// Clean the args array
		if (isset($args[1]))
			$args = $args[1];
		else
			$args = array();
		// Turn restricted variables into named entities
		if ($restrictions) {
			foreach ($restrictions as $var => $regex) {
				if (is_array($regex))
					$regex = '(?:' . implode('|', $regex) . ')';
				$path = preg_replace('`(?<!\(\?):' . $var . '`',
					'(?P<' . $var . '>' . $regex . ')', $path);
			}
		}
		// Turn wildcard variables into named entities
		$path = preg_replace('`(?<!\(\?):\*([a-zA-Z_][a-zA-Z0-9_]*)`',
			'(?P<$1>.+)', $path);
		// Turn all other variables into named entities
		$path = preg_replace('`(?<!\(\?):([a-zA-Z_][a-zA-Z0-9_]*)`',
			'(?P<$1>[^/]+)', $path);
		return '`^' . $path . '$`';
	}
	
	/**
	 * Shortcut to calling {@link #request} with the PUT http method.
	 *
	 * @param string $path The path pattern to match to the request.  See
	 * 		the {@link \hydrogen\controller\Router} for more information.
	 * @param array $defaults An associative array linking variable names to
	 * 		the default value they should have if they are not overridden by
	 * 		a section of the URL.
	 * @param array $overrides An associative array linking variable names to
	 * 		what they should be set to after this rule matches and all other
	 * 		variables are processed.  See the documentation for
	 * 		{@link \hydrogen\controller\Router} for more information.
	 * @param array $restrictions An associative array linking variables in the
	 * 		URL to a regex pattern that those variables must match in order for
	 * 		the rule as a whole to match.  Alternatively, the value can be an
	 * 		array of legal words for that particular variable instead of a
	 * 		regex string.
	 * @param array $argOrder An array indicating the order that the variables
	 * 		should have when passed to the controller function.
	 * @param boolean $argsAsArray true to call the controller function with
	 * 		a single argument: an associative array with a key/value pair
	 * 		or each variable. false (default) to pass each variable as a
	 * 		separate parameter.  Note that, if true, any variable transformed
	 * 		with {@link EXPAND_PARAMS} will instead be processed as though it
	 * 		were transformed with {@link EXPAND_ARRAY}.
	 * @return true if the rule was successfully added; false if the rule has
	 * 		already been loaded from the cached set.
	 */
	public function put($path, $defaults=null, $overrides=null,
			$restrictions=null, $argOrder=null, $argsAsArray=false) {
		return request($path, $defaults, $overrides, $restrictions,
			$argOrder, $argsAsArray, 'PUT');
	}
	
	public function request($path, $defaults=null, $overrides=null,
			$restrictions=null, $argOrder=null, $argsAsArray=false,
			$httpMethod=null) {
		// Early exit if we already have the rules set up
		if ($this->rulesFromCache)
			return false;
		// Set up the new rule
		$this->ruleSet[] = array(
			'method' => $httpMethod ?: false,
			'regex' => $this->processPath($path, $restrictions, $args),
			'defaults' => $defaults,
			'overrides' => $this->processOverrides($overrides),
			'args' => $argOrder ?: $args,
			'argArray' => !!$argsAsArray
		);
		return true;
	}
	
	public function setGlobalOverrides($overrides) {
		// Early exit if we already have the rules set up
		if ($this->rulesFromCache)
			return false;
		$this->globalOverrides = $overrides;
		return true;
	}
	
	public function start() {
		// Get a proper PATH_INFO
		$path = isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] ?
			$_SERVER['PATH_INFO'] : '/';
		$cm = RECacheManager::getInstance();
		// Cache the rules if they're not already there.
		if ($this->doRuleCache && !$this->rulesFromCache) {
			$cm->set("Router:" . $this->name, $this->ruleSet,
				$this->expireTime !== null ? $this->expireTime :
				self::DEFAULT_CACHE_TIME, $this->groups);
		}
		// Is this exact route cached?
		if ($this->doRouteCache &&
				$rule = $cm->get("Router:" . $this->name . ":$path")) {
			$success = $this->passRequest($rule['controller'],
				$rule['function'], $rule['args'], $rule['argProtect']);
			if ($success)
				return true;
		}
		
		// Iterate through the rules until a match is found
		foreach ($this->ruleSet as $rule) {
			if ((!$rule['method'] ||
					$rule['method'] == $_SERVER['REQUEST_METHOD']) &&
					preg_match($rule['regex'], $path, $vars)) {
				// Collect all the variables
				if ($rule['defaults'])
					$vars = array_merge($rule['defaults'], $vars);
				// Apply the overrides
				$arraysAsParams = array();
				if ($rule['overrides']) {
					$this->applyOverrides($vars, $rule['overrides'],
						$arraysAsParams);
				}
				// At this point, we must have a controller and function
				if (!isset($vars[self::KEYWORD_CONTROLLER]) ||
						!isset($vars[self::KEYWORD_FUNCTION])) {
					throw new RouteSyntaxException(
						"Matched route requires both a '" .
						self::KEYWORD_CONTROLLER . "' and a '" .
						self::KEYWORD_FUNCTION . "' variable.");
				}
				// Collect the arguments to be sent to the function in the
				// requested format.
				$args = array();
				$variableParams = false;
				if ($rule['args']) {
					// Array keys format
					if (isset($rule['argArray']) && $rule['argArray']) {
						foreach ($rule['args'] as $key) {
							if (isset($vars[$key]))
								$args[$key] = $vars[$key];
						}
						$args = array($args);
					}
					// Parameters format
					else {
						$variableParams = true;
						foreach ($rule['args'] as $key) {
							if (isset($vars[$key])) {
								if (is_array($vars[$key]) &&
										isset($arraysAsParams[$key]))
									$args = array_merge($args, $vars[$key]);
								else
									$args[] = $vars[$key];
							}
						}
					}
				}
				// Pass the request!
				$success = $this->passRequest($vars[self::KEYWORD_CONTROLLER],
					$vars[self::KEYWORD_FUNCTION], $args, $variableParams);
				if ($success) {
					// Cache the route
					if ($this->doRouteCache) {
						$cm->set("Router:" . $this->name . ":$path", array(
							'controller' => $vars[self::KEYWORD_CONTROLLER],
							'function' => $vars[self::KEYWORD_FUNCTION],
							'args' => $args,
							'argProtect' => $variableParams
							), $this->expireTime ?: self::DEFAULT_CACHE_TIME,
							$this->groups);
					}
					return true;
				}
			}
		}
		return false;
	}
}

?>