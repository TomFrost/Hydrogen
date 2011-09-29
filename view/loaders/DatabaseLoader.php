<?php
/*
 * Created by Jack Harley/AG
 */

namespace hydrogen\view\loaders;

use hydrogen\config\Config;
use hydrogen\view\View;
use hydrogen\view\Loader;
use hydrogen\database\Query;
use hydrogen\view\exceptions\NoSuchViewException;

class DatabaseLoader implements Loader {
	
	/**
	 * Database template loader
	 * Assumes that the table specified in the config has
	 * a 'path' and 'content' field.
	 * The former should contain the path as it would be
	 * loaded through a controller (e.g. ucp/user_logged_in)
	 * The latter should contain the Hydrogen/purephp template
	 * as it would be in a file.
	 *
	 * @param string viewName The name of the view to be found.
	 */
	public function load($viewName) {
		$table = Config::getRequiredVal("view", "table_name");
		$nameCol = Config::getRequiredVal("view", "name_field");
		$contentCol = Config::getRequiredVal("view", "content_field");
		
		$query = new Query("SELECT");
		$query->field($contentCol);
		$query->from($table);
		$query->where($nameCol . " = ?", $viewName);
		$query->limit(1);
		$stmt = $query->prepare();
		$stmt->execute();
		$result = $stmt->fetchObject();
		
		if (!$result->$contentCol) {
			throw new NoSuchViewException("View " . $viewName .
				" does not exist in database.");
		}
		return $result->$contentCol;
	}
}

?>