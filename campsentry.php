#!/usr/local/bin/php
<?php

require_once 'Zend/Loader.php';

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__)));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
  realpath(APPLICATION_PATH . '/library'),
  get_include_path(),
  )));

class Campsentry
{

  public $config;
  public $opts;
  public $args;
  public $client;

  public function __construct()
  {
    Zend_Loader::loadClass('Zend_Console_Getopt');
    Zend_Loader::loadClass('Zend_Config_Ini');
    Zend_Loader::loadClass('Zend_Http_Client');
    Zend_Loader::loadClass('Zend_Cache');
    Zend_Loader::loadClass('Zend_Db');

    // Get config items using Zend config
    // $this->config = new Zend_Config_Ini(APPLICATION_PATH . '/config/config.ini', 'account');

    // Get commands from the CLI
    $this->opts = new Zend_Console_Getopt('abp:');
    $this->client = new Zend_Http_Client();
  }
 
  public function cli_arguments()
  {
    $this->opts = new Zend_Console_Getopt('abp:');
    $command = $this->opts->getRemainingArgs();

    switch ($command[0]) {
      case 'test':
        echo "i equals 0\n";
        break;
      case 'test1':
        echo "i equals 1\n";
        break;
      case 'test2':
        echo "i equals 2\n";
        break;
    }
  }

  /** 
  public fucntion cli_arguments();
  {
    $args = $this->opts->getRemainingArgs();
    $client->setAuth($config->token, 'myPassword!');
    $client->setUri($config->basecamp->uri->base.$config->basecamp->uri->listprojects);
    $client->request('GET');
    $response = $client->request();

    if ($response->getStatus() == 200) 
    {
      echo "The request returned the following information:<br />";
      $data = simplexml_load_string($response->getBody());
      $id = (string) $data->project[1]->name;
      print_r($id);
    } 
    else 
    { 
      echo "An error occurred while fetching data:<br />";
      echo $response->getStatus() . ": " . $response->getMessage();
    }
  }
  **/

  public function db_connect()
  {
    $config = new Zend_Config_Ini(APPLICATION_PATH . '/config/config.ini', 'account');
    $config_db = new Zend_Config(
    array(
      'database' => array(
        'adapter' => 'Mysqli',
          'params'  => array(
            'host'     => $config->mysql->host,
            'dbname'   => $config->mysql->database,
            'username' => $config->mysql->username,
            'password' => $config->mysql->password,
          )
        )
      )
    );
    $init_db = Zend_Db::factory($config_db->database); 
    return $init_db;
  }

  public function save_db()
  {
    $connect = $this->db_connect();  
    $sql = 'SELECT * FROM cs_commits';
    $result = $connect->fetchAll($sql, 2);
    print_r($result);
  }

  public function save_cache()
  {
    $frontendOptions = array('lifetime' => NULL);
    $backendOptions = array('cache_dir' => 'tmp');
    $cache = Zend_Cache::factory('Output', 'File', $frontendOptions, $backendOptions);
    if( ($result = $cache->load('myresult')) === false ) 
    {
      $result = "bar";
      $cache->save($result, 'myresult');
    }
  }
}

$foo = new Campsentry();
$foo->cli_arguments();

?>
