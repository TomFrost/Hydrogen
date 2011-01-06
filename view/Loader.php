<?php
/*
 * Copyright (c) 2009 - 2011, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view;

/**
 * The Loader interface defines a class is that capable of loading the contents
 * of a template from some source; whether that source be a file, a database,
 * or some other storage system.
 *
 * Loaders should be placed in the hydrogen/view/loaders folder, declared
 * under the \hydrogen\view\loaders namespace, and named ______Loader,
 * where the blank is the name of the loader with a capital first letter.  They
 * should implement this interface and its method, then can be selected for use
 * in the hydrogen.autoconfig.php file.
 *
 * Loaders should never be instantiated directly.  Instead, get an instance
 * of the loader from {@link \hydrogen\view\LoaderFactory::getLoader()}.
 */
interface Loader {
	
	/**
	 * Loads the contents of the specified template.
	 *
	 * @param string $templateName The name of the template to be loaded.
	 * @return string The unaltered, unparsed contents of the specified
	 * 		template.
	 * @throws hydrogen\view\exceptions\NoSuchViewException if the specified
	 * 		template is not found or cannot be loaded.
	 */
	public function load($templateName);
}

?>