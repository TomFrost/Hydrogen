<?php
/*
 * Copyright (c) 2009 - 2010, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\nodes;

use hydrogen\view\engines\hydrogen\Node;
use hydrogen\view\engines\hydrogen\PHPFile;
use hydrogen\config\Config;

class UrlNode implements Node {
	protected $folders;
	protected $kvPairs;

	public function __construct($folders, $kvPairs) {
		$this->folders = $folders;
		$this->kvPairs = $kvPairs;
	}

	public function render($phpFile) {
		$appURL = Config::getRequiredVal('general', 'app_url');
		while ($appURL[strlen($appURL) - 1] === '/')
			$appURL = substr($appURL, 0, -1);
		foreach ($this->folders as $folder) {
			if (is_object($folder)) {
				$appURL .= '/' . PHPFile::PHP_OPENTAG . 'echo ' .
					$folder->getVariablePHP($phpFile) . ';' .
					PHPFile::PHP_CLOSETAG;
			}
			else
				$appURL .= '/' . $folder;
		}
		if ($this->kvPairs) {
			if (strtolower(substr($appURL, -4)) !== '.php')
				$appURL .= '/';
			$appURL .= '?';
			foreach ($this->kvPairs as $key => $val) {
				$appURL .= $key . '=';
				if (is_object($val)) {
					$appURL .= PHPFile::PHP_OPENTAG . 'echo ' .
						$val->getVariablePHP($phpFile) . ';' .
						PHPFile::PHP_CLOSETAG;
				}
				else
					$appURL .= $val;
				$appURL .= '&';
			}
			$appURL = substr($appURL, 0, -1);
		}
		$phpFile->addPageContent($appURL);
	}
}

?>