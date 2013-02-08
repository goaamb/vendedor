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
	public function offlineAccess($x = false) {
		$this->preheader = array_merge ( $this->preheader, array (
				"isFacebook" => $this->facebook->getUser () 
		) );
		if ($this->checkPermission ( array (
				'publish_stream',
				'manage_pages' 
		) )) {
			$accessToken = $this->facebook->setExtendedAccessToken ();
			$res = $this->db->query ( "select codigo,cuenta from fsesion where fid='" . $this->facebook->getUser () . "'" )->result ();
			if ($res && is_array ( $res ) && count ( $res ) > 0) {
				if ($res [0]->codigo !== $accessToken) {
					$cuenta = intval ( $res [0]->cuenta ) + 1;
					$this->db->update ( "fsesion", array (
							"codigo" => $accessToken,
							"fecha" => date ( "Y-m-d H:i:s" ),
							"cuenta" => $cuenta 
					), array (
							"fid" => $this->facebook->getUser () 
					) );
				}
			} else {
				$this->db->insert ( "fsesion", array (
						"fid" => $this->facebook->getUser (),
						"codigo" => $accessToken,
						"fecha" => date ( "Y-m-d H:i:s" ),
						"cuenta" => 1 
				) );
			}
			print $accessToken;
		} else {
			$this->loadGUI ( "permiso", array (
					"loginUrl" => $this->facebook->getLoginUrl ( array (
							'canvas' => 1,
							'scope' => 'publish_stream,manage_pages',
							'fbconnect' => 0 
					) ) 
			), $this->preheader );
		}
	}
	public function index() {
		$this->preheader = array_merge ( $this->preheader, array (
				"isFacebook" => $this->mysession->userdata ( "facebook" ) 
		) );
		if ($this->checkPermission ()) {
			if ($this->iLike ()) {
				parent::nuevo ();
			} else {
				$this->loadGUI ( "no_gusta", $this->preheader );
			}
		} else {
			$this->loadGUI ( "permiso", array (
					"loginUrl" => $this->facebook->getLoginUrl ( array (
							'canvas' => 1,
							'scope' => 'user_about_me,user_birthday,user_photos,email',
							'fbconnect' => 0 
					) ) 
			), $this->preheader );
		}
	}
	public function product($id) {
		$this->preheader = array_merge ( $this->preheader, array (
				"isFacebook" => $this->mysession->userdata ( "facebook" ) 
		) );
		$data = array (
				"articulo" => $this->articulo->darArticulo ( $id ) 
		);
		$this->loadGUI ( "articulo/facebook_item", $data );
	}
	private function checkPermission($adicional = array('user_about_me',
							'user_birthday',
							'user_photos',
							'email'
 )) {
		$permit = true;
		try {
			$usuario = $this->facebook->getUser ();
			if ($usuario) {
				$permissions = $this->facebook->api ( "/$usuario/permissions" );
				if (is_array ( $permissions ) && count ( $permissions ) > 0 && isset ( $permissions ['data'] ) && is_array ( $permissions ['data'] ) && count ( $permissions ['data'] ) > 0) {
					$myAppPermit = array ();
					if (is_array ( $adicional )) {
						$myAppPermit = array_merge ( $myAppPermit, $adicional );
					}
					foreach ( $myAppPermit as $mp ) {
						if (! array_key_exists ( $mp, $permissions ['data'] [0] )) {
							$permit = false;
							continue;
						}
					}
				} else {
					return false;
				}
			} else {
				$permit = false;
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