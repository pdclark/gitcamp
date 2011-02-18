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
  }
 

  public function run()
  {
    $this->opts = new Zend_Console_Getopt('abp:');
    $command = $this->opts->getRemainingArgs();
    
    if(!isset($command[0]))
    {
      echo "Enter a command\n";
    }
    else
    {
      $this->cli_arguments($command);
    }

  }

  public function cli_arguments($command)
  {
    switch ($command[0]) {
      case 'list':
        $this->list_projects();
        break;
      case 'set':
        $this->set_project();
        break;
      case 'test2':
        echo "i equals 2\n";
        break;
    }
  }

  public function list_projects()
  {

    $config = new Zend_Config_Ini(APPLICATION_PATH . '/config/config.ini', 'api');
    $client = new Zend_Http_Client();
    $client->setAuth($config->basecamp->token, 'myPassword!');
    $client->setUri($config->basecamp->uri->base.$config->basecamp->uri->listprojects);
    $client->request('GET');
    $response = $client->request();

    if ($response->getStatus() == 200) 
    {
      echo "Success\n";
      $data = simplexml_load_string($response->getBody());
      foreach($data as $row)
      {
        echo (string) $row->id[0] . ":" . $row->name[0]  . "\n";
      }
    } 
    else 
    { 
      echo "Failure\n";
      echo $response->getStatus() . ": " . $response->getMessage() . "\n";
    }
  }

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

  public function set_project($project)
  {
    $frontendOptions = array('lifetime' => NULL);
    $backendOptions = array('cache_dir' => 'tmp');
    $cache = Zend_Cache::factory('Output', 'File', $frontendOptions, $backendOptions);
    if( ($project = $cache->load('project')) === false ) 
    {
      $project = "bar";
      $cache->save($project, 'project');
    }
  }
}

$foo = new Campsentry();
$foo->run();

?>
