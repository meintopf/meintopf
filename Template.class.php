<?php
/* *************************************
 *   Very simple templating system
 *   http://stackoverflow.com/a/95027
 * *************************************/
class Template {
	private $args;
	private $file;

	public function __get($name) {
		return $this->args[$name];
	}

	public function __construct($file, $args = array()) {
		$this->file = dirname( __FILE__ )."/templates/".$file;
		$this->args = $args;
	}
	
	// render template to stdout
	public function render() {
		include $this->file;
	}
	
	// render template to a string
	public function renderToString() {
		ob_start();
		$this->render();
		return ob_get_clean();
	}
}
