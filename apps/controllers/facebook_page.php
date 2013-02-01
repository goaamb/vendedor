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
	}
	public function index() {
		$this->preheader = array_merge ( $this->preheader, array (
				"isFacebook" => $this->mysession->userdata ( "facebook" ) 
		) );
		if ($this->iLike ()) {
			parent::nuevo ();
		} else {
			$this->loadGUI ( "no_gusta", $this->preheader );
		}
	}
	public function product($id) {
		$data = array (
				"articulo" => $this->articulo->darArticulo ( $id ) 
		);
		$this->loadGUI("articulo/facebook_item",$data);
	}
	private function checkPermission() {
		$permit = true;
		try {
			$usuario = $this->mysession->userdata ( "facebook" );
			$permissions = $this->facebook->api ( "/$usuario/permissions" );
			if (is_array ( $permissions ) && count ( $permissions ) > 0 && isset ( $permissions ['data'] ) && is_array ( $permissions ['data'] ) && count ( $permissions ['data'] ) > 0) {
				$myAppPermit = array (
						'user_about_me',
						'user_birthday',
						'user_photos',
						'publish_actions',
						'email',
						'publish_stream',
						'share_item' 
				);
				foreach ( $myAppPermit as $mp ) {
					if (! array_key_exists ( 'publish_actions', $permissions ['data'] [0] )) {
						$permit = false;
						continue;
					}
				}
			} else {
				return false;
			}
		} catch ( Exception $ex ) {
			$permit = false;
		}
		return $permit;
	}
	private function iLike() {
		$signed_request = $this->facebook->getSignedRequest ();
		if (isset ( $signed_request ["page"] )) {
			$like_status = $signed_request ["page"] ["liked"];
			return $like_status;
		}
		return false;
	}
}

?>