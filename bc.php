#!/usr/bin/php
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
	
	public $tasks;
	public $active_todo_list = array();
	public $todo_index = array();
	public $project_list = array();
	public $project;
	
	public $script_name;
	
	public $cache;
	public $basecamp;

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
		
		$this->basecamp = new Basecamp();
		$this->cache = new Cache_model();
		
		$this->script_name = 'bc';
		$this->run();
	}
 
	public function run()
	{
		$this->opts = new Zend_Console_Getopt('abp:');
		if ( empty($this->args) ) { $this->args = $this->opts->getRemainingArgs(); }
		
		$this->project = $this->cache->get_project( $this->basecamp );
		$this->project_list = $this->cache->get_project_list( $this->basecamp );
		$this->active_todo_list = $this->cache->get_active_todo_list( $this->project, $this->basecamp );
		
		$method = $this->args[0];
		if ( method_exists($this, $method ) ) {
			$this->$method();
		}
		
		if ( empty( $this->project ) ) {
			$this->set_project();
			$this->run();
			exit;
		}else {
			
			$this->set_todo_index();
			echo "\n** {$this->project['name']} ** active.\nChange with $this->script_name reload.\n\n";
		}

		$this->clear();
		$this->tasks = $this->cache->get_tasks( $this->project['id'], $this->active_todo_list['id'], $this->basecamp );
		
		
		foreach ($this->tasks as $key => $t ) {
			extract($t);
			// echo "[$id] $content\n";
			$copy .= "[$id] $content \n";
		}
		exec('echo "'.$copy.'" | mate;');
		
		
		exit(0);
		
		/*

		if(!isset($command[0]))
		{
			echo "Enter a command\n";
		}
		elseif($command[0] === "project" || $command[0] === "p")
		{

			if(!isset($command[1]))
			{
				$command[1] = '';
			}

			switch ($command[1]) 
			{
				case 'set':
					$cache->set_project($command[2]);
					break;
				case 'show':
					$cache->get_project();
					break;
				case 'debug':
					$basecamp->list_projects();
					break;
				case 'list':
		default:
			echo 'Loading... ';
					$basecamp->list_projects();
					break;
			}
		}
		elseif($command[0] === "todolist" || $command[0] === "todo" || $command[0] === "t")
		{
			$projects = $cache->get_project_list();
			
			foreach ($projects as $key => $p ) {
				echo "[$key] {$p['name']}\n";
			}
			echo "Which project?\n";
			
			$project_id = trim(fgets(STDIN));
			
			echo "{$projects[$project_id]['name']} made active. Change with bc project.\n";
	
		
			if(!isset($command[1]))
			{
				$command[1] = '';
			}

			switch ($command[1]) 
			{
				case 'set':
					$cache->set_project($command[2]);
					break;
				case 'show':
		case 'list':
				default:
			fwrite(STDOUT, "Hello...\nWhat is your name? ");
			$name = trim(fgets(STDIN));
			fwrite(STDOUT, "Hello, $name!\n");

			ob_start();
			$project = $cache->get_project();
			ob_end_clean();
			$list = $command[2];
			if ( empty($project) ) {
				echo "Please set a project with: todo set ###\nLoading... ";
						$basecamp->list_projects();
				
			}else if ( empty($list) ){
				echo "Display which list? \nLoading... ";
				$basecamp->project_get_all_lists( $project );
			}else {
				$basecamp->get_todo_items($project, $list);
			}
					break;
				case 'debug':
					$basecamp->list_projects();
					break;
		}
	
		}*/
	}
	
	public function project() {
		$this->active_todo_list = $this->cache->set_active_todo_list( null );
		$this->set_project();
	}
	
	public function set_project() {
		foreach ($this->project_list as $key => $p ) {
			echo "[$key] {$p['name']}\n";
		}
		$key = $this->input('Which project?');

		$this->cache->set_project( $this->project_list[$key] );
	}
	
	public function set_todo_index() {
		
		$this->todo_index = $this->cache->get_todo_index( $this->project['id'], $this->basecamp );
		
		if ( empty( $this->todo_index ) ) {
			echo "All todo lists for {$this->project['name']} are complete.\n\n";
			$this->cache->set_project(null, $this->basecamp);
			$this->cache->set_todo_index(null, $this->basecamp);
			$this->run();
			exit;
		}

		foreach ($this->todo_index as $key => $p ) {
			echo "[$key] {$p['name']}\n";
		}
		$key = $this->input('Which todo list?');
		$this->active_todo_list = $this->todo_index[$key];

		$this->cache->set_active_todo_list( $this->project, $this->active_todo_list );
	
	}
	
	public function set_tasks() {
		//echo "\n\n";
		//print_r( $this->cache->get_active_todo_list( $this->project, $this->basecamp ) );
	}
	
	public function reload() {
		$this->cache->set_project_list(null, $this->basecamp);
		$this->cache->set_project(null, $this->basecamp);
		$this->project_list = $this->cache->set_project_list( null, $this->basecamp );
		$this->active_todo_list = $this->cache->set_active_todo_list( null, $this->basecamp );
		$this->args[0] = null;
		$this->run();
	}
	
	public function input($prompt) {
		echo "$prompt ";
		return trim(fgets(STDIN));
	}
	
	public function clear($out = TRUE) {
	    $clearscreen = chr(27)."[H".chr(27)."[2J";
	    if ($out) print $clearscreen;
	    else return $clearscreen;
	  }
}

$foo = new Campsentry();

?>
