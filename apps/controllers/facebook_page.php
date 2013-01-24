<?php

require_once ('articulo.php');
class Facebook_page extends Articulo {
	public function __construct() {
		parent::__construct ();
		$this->load->library ( "facebook" );
		$usuario = $this->mysession->userdata ( "facebook" );
		if (! $usuario) {
			$this->mysession->set_userdata ( "facebook", $this->facebook->getUser () );
		}
		var_dump($usuario);
	}
	public function index() {
		$this->preheader = array_merge ( $this->preheader, array (
				"isFacebook" => true 
		) );
		parent::nuevo ();
	}
}

?>