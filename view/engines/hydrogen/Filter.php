<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen;

/**
 * Filters are (generally) simple transformations that can be applied to
 * most any variable used in a Hydrogen template.  Most often, filters can
 * be found as part of a variable tag, but are also legal in many other tags
 * where variables can be used.
 *
 * The purpose of a Filter class is to return PHP code that, when executed,
 * will apply some filter to a given PHP element.  This element may be a
 * variable, a hardcoded string, or a value with other functions/filters
 * already wrapped around it.  The code that a filter outputs must work
 * regardless of the type of element passed to it.
 *
 * Filters are also passed an instance of
 * {@link \hydrogen\view\engines\hydrogen\PHPFile}, which they can use to
 * insert variables and functions into the compiled PHP page to assist in
 * more complex operations.
 *
 * Finally, filters have full control over whether their resulting data should
 * be escaped with PHP's htmlentities() function.  A filter should turn this
 * autoescaping off if and <em>only</em> if it can guarantee that there will
 * be nothing necessary to escape in the result.  If a filter adds HTML or
 * other similar markup to a string, it should manually escape the string
 * prior to adding its code, then turn escaping off.  That is, of course,
 * if the filter was called with escaping turned on.
 *
 * Classes that implement the Filter interface should throw a
 * {@link \hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException}
 * if an incorrect number of arguments have been passed to it.
 */
interface Filter {
	
	/**
	 * Applies PHP code to the given string that is necessary for executing
	 * a filter on PHP data.  The filter should not be directly applied to
	 * the string, as the string is likely PHP code itself.  Rather, PHP
	 * code should be added to the string to produce the desired result when
	 * executed.  See the filters within the
	 * \hydrogen\view\engines\hydrogen\filters namespace for examples.
	 *
	 * @param string $string The string to which the filter PHP should be
	 * 		added.
	 * @param array $args An array of
	 * 		{@link \hydrogen\view\engines\hydrogen\FilterArgument} objects.
	 * @param boolean $escape A value, passed by reference, that determines
	 * 		whether or not the result of this filter will be automatically
	 * 		escaped.  Changing this value inside the function can control
	 * 		whether or not this escaping will take place.  See the Filter
	 * 		interface documentation for guidelines on when this value should be
	 * 		changed, and the guidelines for doing it.
	 * @param \hydrogen\view\engines\hydrogen\PHPFile $phpfile An instance
	 * 		of PHPFile to which variables and functions can be added to
	 * 		assist this filter.  Filters, however, should never call the
	 * 		{@link \hydrogen\view\engines\hydrogen\PHPFile::addPageContent()}
	 * 		function.  All PHP code for the page body should be returned by
	 * 		this function.
	 * @return string The PHP code with the data provided in $string that will
	 * 		produce the string with the appropriate filter applied when
	 * 		executed.
	 * @throws \hydrogen\view\engines\hydrogen\exceptions\TemplateSyntaxException
	 * 		when the improper number of arguments is provided to the Filter.
	 */
	public static function applyTo($string, $args, &$escape, $phpfile);
}

?>