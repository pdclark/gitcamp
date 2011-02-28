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
    // Load Zend Classes
    Zend_Loader::loadClass('Zend_Console_Getopt');
    Zend_Loader::loadClass('Zend_Config_Ini');
    Zend_Loader::loadClass('Zend_Http_Client');
    Zend_Loader::loadClass('Zend_Cache');
    Zend_Loader::loadClass('Zend_Db');
    
    // Locad app files
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

    $cache = new Cache_model();
    
    if(!isset($command[0]))
    {
      echo "Enter a command\n";
    }
    elseif($command[0] === "project")
    {

      if(!isset($command[1]))
      {
        $command[1] = '';
      }

      switch ($command[1]) 
      {
        case 'list':
          $basecamp->list_projects();
          break;
        case 'set':
          $cache->set_project($command[2]);
          break;
        case 'show':
          $cache->get_project();
          break;
        case 'debug':
          $basecamp->list_projects();
          break;
        default:
          $basecamp->get_project();
          break;
      }
    }
    elseif($command[0] === "todolist")
    {

      if(!isset($command[1]))
      {
        $command[1] = '';
      }

      switch ($command[1]) 
      {
        case 'list':
          $basecamp->project_get_all_lists($command[2]);
          break;
        case 'set':
          $cache->set_project($command[2]);
          break;
        case 'show':
          $cache->get_project();
          break;
        case 'debug':
          $basecamp->list_projects();
          break;
        default:
          $basecamp->get_project();
          break;
      }
    }
  }
}

$foo = new Campsentry();
$foo->run();

?>
