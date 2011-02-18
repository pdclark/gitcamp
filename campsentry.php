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
    include_once(APPLICATION_PATH . '/connectors/api.php');
    include_once(APPLICATION_PATH . '/connectors/db.php');
    include_once(APPLICATION_PATH . '/lib/bc.php');
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

    $basecamp = new Bc();

    switch ($command[0]) {
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

  public function save_db()
  {
    $connect = $this->db_connect();  
    $sql = 'SELECT * FROM cs_commits';
    $result = $connect->fetchAll($sql, 2);
    print_r($result);
  }

  public function get_project()
  {

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
