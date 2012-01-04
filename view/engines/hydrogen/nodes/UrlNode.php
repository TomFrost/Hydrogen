<?php
/*
 * Copyright (c) 2009 - 2012, Frosted Design
 * All rights reserved.
 */

namespace hydrogen\view\engines\hydrogen\nodes;

use hydrogen\view\engines\hydrogen\Node;
use hydrogen\view\engines\hydrogen\PHPFile;
use hydrogen\config\Config;

class UrlNode implements Node {
	protected $folders;
	protected $kvPairs;
	protected $relative;

	public function __construct($folders, $kvPairs, $relative) {
		$this->folders = $folders;
		$this->kvPairs = $kvPairs;
		$this->relative = $relative;
	}

	public function render($phpFile) {
		$appURL = $this->relative ?
			Config::getRequiredVal('general', 'app_url') : '';
		while ($appURL && $appURL[strlen($appURL) - 1] === '/')
			$appURL = substr($appURL, 0, -1);
		$first = true;
		foreach ($this->folders as $folder) {
			if ($first && !$this->relative) {
				$appURL .= $folder;
				$first = false;
			}
			else {
				if (is_object($folder)) {
					$appURL .= '/' . PHPFile::PHP_OPENTAG . 'echo urlencode(' .
						$folder->getVariablePHP($phpFile) . ');' .
						PHPFile::PHP_CLOSETAG;
				}
				else
					$appURL .= '/' . urlencode($folder);
			}
		}
		if ($this->kvPairs) {
			if (strtolower(substr($appURL, -4)) !== '.php')
				$appURL .= '/';
			$appURL .= '?';
			foreach ($this->kvPairs as $key => $val) {
				$appURL .= $key . '=';
				if (is_object($val)) {
					$appURL .= PHPFile::PHP_OPENTAG . 'echo urlencode(' .
						$val->getVariablePHP($phpFile) . ');' .
						PHPFile::PHP_CLOSETAG;
				}
				else
					$appURL .= urlencode($val);
				$appURL .= '&';
			}
			$appURL = substr($appURL, 0, -1);
		}
		$phpFile->addPageContent($appURL);
	}
}

?>