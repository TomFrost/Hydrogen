<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

/**
 * This interface defines the only function that a template engine is required
 * to have to be used with the Hydrogen View library.  All templating engines
 * used with Hydrogen are provided with the name of a template to load and an
 * instance of {@link \hydrogen\view\Loader} that's capable of loading its
 * contents, and the templating engine's job is to return a pure PHP page that
 * can be cached and called on in the future rather than re-compiling a new
 * template with each request.
 *
 * All template engines should exist in their own folder (named the same as
 * the template engine, in all-lowercase letters) within the
 * hydrogen/view/engines folder.  That folder should contain, at minimum, one
 * PHP file with a class named _________Engine (where the blank is the template
 * name starting with a capital letter) in the namespace
 * \hydrogen\view\engines\_________ (where the blank is the engine name in
 * all lowercase letters).
 *
 * The ______Engine class should implement this interface, and include the
 * function it defines.  The organization and functionality of that template
 * engine, from that point on, is entirely up to the developer!
 */
interface TemplateEngine {

	/**
	 * Gets the raw PHP code which, when executed, will produce the desired
	 * output from this template.  Generated PHP code should be completely
	 * agnostic of variable types and values, as these things could change from
	 * execution to execution.
	 *
	 * @param $templateName The name of the template to be parsed by this
	 * 		engine.
	 * @param \hydrogen\view\Loader $loader The instance of Loader that should
	 * 		be used to load the contents of the given template, as well as
	 * 		any other template file that may be required during the parsing
	 * 		process.
	 * @return string Raw PHP code that can be executed with eval() or cached
	 * 		to a file and executed repeatedly with different values for the
	 * 		context variables.
	 */
	public static function getPHP($templateName, $loader);

}

?>