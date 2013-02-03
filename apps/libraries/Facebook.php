<?php

require_once ('apps/extra/facebook.php');
class Facebook extends Real_Facebook {
	public function __construct() {
		Facebook::$CURL_OPTS [CURLOPT_CAINFO] = pathinfo ( __FILE__, PATHINFO_DIRNAME ) . '/../extra/fb_ca_chain_bundle.crt';
		Facebook::$CURL_OPTS [CURLOPT_SSL_VERIFYPEER] = false;
		$url = explode ( "/", $_SERVER ["REQUEST_URI"] );
		array_pop ( $url );
		$url = array_pop( $url );
		if ($url == "offlineAccess") {
			parent::__construct ( array (
					'appId' => "429698633772942",
					'secret' => "1265eab52bf73d4eaa3d78c16e9a3eed",
					"cookie" => true 
			) );
		} else {
			parent::__construct ( array (
					'appId' => '108831492599921',
					'secret' => 'adab39978c11f83f78a3f10274dbbd9c',
					"cookie" => true 
			) );
		}
	}
}

?>