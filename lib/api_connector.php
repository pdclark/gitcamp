<?php

Class Api_connector
{
	public $apitoken;
	public $base;

	function __construct( $subdomain, $apitoken ) {
		$this->base = 'https://'.$subdomain.'.basecamphq.com';
		$this->apitoken = $apitoken;
	}

	public function api_connect($call, $request = 'GET')
	{
		$client = new Zend_Http_Client();
		$client->setAuth($this->apitoken, 'password');
		$client->setUri($this->base.$call);
		$client->request( $request );
		try {
			$response = $client->request();
		}catch (Exception $e) {
			return false;
		}
	
		if ($request == 'GET') {
			if ($response->getStatus() == 200) {
				return simplexml_load_string($response->getBody());
			} 
			else { 
				echo $response->getStatus() . ": " . $response->getMessage() . "\n";
				return false;
			}
		}else if ($request == 'PUT') {
			if ($response->getStatus() == 200) {
				return true;
			} 
			else { 
				echo $response->getStatus() . ": " . $response->getMessage() . "\n";
				return false;
			}
		}
		
		
	}

}

?>
