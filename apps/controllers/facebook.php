<?php
require_once 'basecontroller.php';
class Facebook extends BaseController {
	public function index() {
	
	}
	public function connect() {
		$this->load->helper ( "url" );
		$this->load->library ( "mysession" );
		$this->load->database ();
		$api_key = "108831492599921";
		$api_secret = "adab39978c11f83f78a3f10274dbbd9c";
		
		$this->mysession->unset_userdata ( 'user_fb_token' );
		
		if ($this->input->get ( 'code' ) != "") {
			$this->mysession->set_userdata ( 'fb_code', $this->input->get ( 'code' ) );
		}
		$code = $this->mysession->userdata ( 'fb_code' );
		try {
			if ($code != "") {
				$acces_token = "https://graph.facebook.com/oauth/access_token?client_id=" . $api_key . "&redirect_uri=" . urlencode ( base_url () . 'facebook/connect/' ) . "&client_secret=" . $api_secret . "&code=" . $code;
				
				$response = file_get_contents ( $acces_token );
				$params = null;
				parse_str ( $response, $params );
				if (! empty ( $acces_token ) && isset ( $params ['access_token'] )) {
					$graph_url = "https://graph.facebook.com/me?access_token=" . $params ['access_token'];
					$user = json_decode ( file_get_contents ( $graph_url ) );
					if ($user != null) {
						$this->load->model ( "Usuario_model", "usuario" );
						$userFB = $this->usuario->darUsuarioXFacebook ( $user->id );
						$this->load->helper ( "url" );
						if (! $userFB) {
							$userFB = $this->usuario->darUsuarioXEmail ( $user->email );
							$this->usuario->fb_id = $user->id;
							$this->usuario->actualizarFacebook ( $user->id );
						}
						if ($userFB) {
							if ($this->usuario->login ()) {
								redirect ( "store/{$this->usuario->seudonimo}" );
							}
						} else {
							$username = $this->usuario->darSeudonimoSimilar ( $user->first_name );
							$password = $this->usuario->generarPassword();
							$this->usuario->seudonimo = $username;
							$this->usuario->password = $password;
							$this->usuario->email = $user->email;
							$this->usuario->fb_id = $user->id;
							if ($this->usuario->registrar ()) {
								redirect ( "store/{$this->usuario->seudonimo}" );
							}
						}
						return;
					}
				}
				redirect ( site_url ( 'login' ), 'refresh' );
			}
		} catch ( Exception $e ) {
		}
	}
}

?>