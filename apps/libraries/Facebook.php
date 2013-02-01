<?php

require_once ('apps/extra/facebook.php');
class Facebook extends Real_Facebook {
	public function __construct() {
		Facebook::$CURL_OPTS [CURLOPT_CAINFO] = pathinfo ( __FILE__, PATHINFO_DIRNAME ) . '/../extra/fb_ca_chain_bundle.crt';
		Facebook::$CURL_OPTS [CURLOPT_SSL_VERIFYPEER] = false;
		parent::__construct ( array (
				'appId' => '108831492599921',
				'secret' => 'adab39978c11f83f78a3f10274dbbd9c',
				"cookie" => true 
		) );
	}
}

?>