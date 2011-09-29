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
	 * Loads the contents of the specified template.
	 *
	 * @param string $templateName The name of the template to be loaded.
	 * @return string The unaltered, unparsed contents of the specified
	 * 		template.
	 * @throws hydrogen\view\exceptions\NoSuchViewException if the specified
	 * 		template is not found or cannot be loaded.
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