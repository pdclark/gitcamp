#!/usr/local/bin/php
<?php

require_once 'Zend/Loader.php';

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__)));

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
    include_once(APPLICATION_PATH . '/connectors/api_connector.php');
    include_once(APPLICATION_PATH . '/connectors/db_connector.php');
    include_once(APPLICATION_PATH . '/lib/basecamp.php');
    include_once(APPLICATION_PATH . '/models/cache_model.php');
    include_once(APPLICATION_PATH . '/models/db_model.php');
  }
 
  public function run()
  {
    $this->opts = new Zend_Console_Getopt('abp:');
    $command = $this->opts->getRemainingArgs();
    $basecamp = new Basecamp();
    
    if(!isset($command[0]))
    {
      echo "Enter a command\n";
    }
    else
    {
      switch ($command[0]) 
      {
        case 'list':
          $this->list_projects();
          break;
        case 'set':
          $this->set_project();
          break;
        case 'debug':
          $basecamp->list_projects();
          break;
      }
    }
  }
}

$foo = new Campsentry();
$foo->run();

?>
