<?php

Class Api_connector
{

  public function api_connect($call)
  {
    $config = new Zend_Config_Ini(APPLICATION_PATH . '/config/config.ini', 'api');
    $client = new Zend_Http_Client();
    $client->setAuth($config->basecamp->token, 'myPassword!');
    $client->setUri($config->basecamp->uri->base.$call);
    $client->request('GET');
    $response = $client->request();

    if ($response->getStatus() == 200) 
    {
      echo "Success\n";
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
