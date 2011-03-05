<?php

Class Api_connector
{
	public $apitoken;
	public $base;

	function __construct( $subdomain, $apitoken ) {
		$this->base = 'https://'.$subdomain.'.basecamphq.com';
		$this->apitoken = $apitoken;
	}

	public function api_connect($call)
	{
		$client = new Zend_Http_Client();
		$client->setAuth($this->apitoken, 'password');
		$client->setUri($this->base.$call);
		$client->request('GET');
		$response = $client->request();

		if ($response->getStatus() == 200) 
		{
			// echo "Success\n";
			return simplexml_load_string($response->getBody());
		} 
		else 
		{ 
			echo "Failure\n";
			echo $response->getStatus() . ": " . $response->getMessage() . "\n";
		}
	}

}

?>
