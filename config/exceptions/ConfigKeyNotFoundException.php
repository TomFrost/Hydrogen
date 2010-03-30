<?php
namespace hydrogen\config\exceptions;

class ConfigKeyNotFoundException extends \Exception {
	protected $caller;
	
	public function __construct($msg, $caller=false) {
		parent::__construct($msg);
		$this->call = $caller;
	}
	
	public function getCaller() {
		return $this->caller;
	}
}

?>