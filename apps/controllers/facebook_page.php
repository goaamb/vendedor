<?php

require_once ('articulo.php');
class Facebook_page extends Articulo {
	public function __construct() {
		parent::__construct ();
	}
	public function index() {
		parent::nuevo ();
	}
}

?>