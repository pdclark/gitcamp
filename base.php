#!/usr/bin/php
<?php

require_once 'Zend/Loader.php';

// Define path to application directory
defined('APPLICATION_PATH')
		|| define('APPLICATION_PATH', realpath(dirname(__FILE__)));

class Base { 

	public $opts;
	public $args;
	public $client;
	
	public $tasks;
	public $active_todo_list = array();
	public $todo_index = array();
	public $project_list = array();
	public $project;
	
	// Loaded from Git & Basecamp
	public $subdomain;
	public $apitoken;
	public $projectid;
	public $projectname;
	
	public $script_name;
	
	public $cache;
	public $basecamp;

	public function __construct()
	{
		// Load Zend Classes
		Zend_Loader::loadClass('Zend_Console_Getopt');
		Zend_Loader::loadClass('Zend_Http_Client');
		Zend_Loader::loadClass('Zend_Cache');
		Zend_Loader::loadClass('Zend_Db');
		
		// Local app files
		include_once(APPLICATION_PATH . '/connectors/api_connector.php');
		include_once(APPLICATION_PATH . '/connectors/db_connector.php');
		include_once(APPLICATION_PATH . '/lib/basecamp.php');
		include_once(APPLICATION_PATH . '/models/cache_model.php');
		include_once(APPLICATION_PATH . '/models/db_model.php');
		
		// CLI arguments
		$this->opts = new Zend_Console_Getopt('abp:');
		if ( empty($this->args) ) { $this->args = $this->opts->getRemainingArgs(); }
		
		$this->script_name = 'base';
		
		$this->run();
	}
	
	public function config() {
		// Called at start of $this->run()
		
		$this->clear();

		// Subdomain
		unset($return);
		exec('git config --get basecamp.subdomain', $return);
		$this->subdomain = $return[0];
		if (empty( $this->subdomain) ) {
			
			$this->subdomain = $this->input("Enter your Basecamp subdomain: ");
			exec( 'git config --global basecamp.subdomain ' . $this->subdomain );
			$this->config();
			
		}
		
		// API Token
		unset($return);
		exec('git config --get basecamp.apitoken', $return);
		$this->apitoken = $return[0];
		if (empty( $this->apitoken) ) {
			
			$this->apitoken = $this->input("Enter your basecamp API Token: ");
			exec( 'git config --global basecamp.apitoken ' . $this->apitoken );
			$this->config();
			
		}else {
			// echo 'API Token: '.$this->apitoken."\n";
		}
		
		
		// Need Basecamp info to get this far
		
		$this->basecamp = new Basecamp( $this->subdomain, $this->apitoken );
		$this->cache = new Cache_model();
		
		// Project list
		$this->project_list = $this->cache->get_project_list( $this->basecamp );
			
		// Project Name & ID
		unset($return);
		exec('git config --get basecamp.projectid', $return);
		$this->projectid = $return[0];
		
		$this->header();
		
		if (empty( $this->projectid ) ) {
			
			echo "\n";
			foreach ($this->project_list as $key => $p ) {
				echo "[$key] {$p['name']}\n";
			}
			echo "\n";
			
			$key = $this->input('Which project is this Git repo for?');
			$this->projectid = $this->project_list[$key]['id'];
			
			exec( 'git config basecamp.projectid ' . $this->projectid ); // Not global
			$this->config();
			
		}else {
			
			foreach ( $this->project_list as $project ) {
				if ( $project['id'] == $this->projectid ) {
					$this->projectname = $project['name'];
				}
			}
		}
		
	}
 
	public function run() {

		$this->config();
		
		$this->active_todo_list = $this->cache->get_active_todo_list( $this->project, $this->basecamp );
		
		$method = $this->args[0];
		if ( method_exists($this, $method ) ) {
			$this->$method();
		}
		
		$this->set_todo_index();
		echo "\n** {$this->projectname} ** active.\nChange with $this->script_name reload.\n\n";

		$this->clear();
		$this->tasks = $this->cache->get_tasks( $this->projectid, $this->active_todo_list['id'], $this->basecamp );
		
		foreach ($this->tasks as $key => $t ) {
			extract($t);
			// echo "[$id] $content\n";
			$copy .= "[$id] $content \n";
		}
		exec('echo "'.$copy.'" | mate;');
		
		
		exit(0);

	}
	
	public function header() {
		$reload = 'Refresh';
		$diff = strlen($this->subdomain) - strlen($reload);
		if ( strlen($this->subdomain) < strlen($reload) ) {
			$pad1 = str_pad('', abs($diff));
		}else {
			$pad2 = str_pad('', $diff);
		}
		
		$this->clear();
		
		echo  $pad1.$this->subdomain;
		if ( !empty($this->projectname) ) echo ': '.$this->projectname;
		echo  "\n".$pad2."$reload: [p]roject  [t]odo lists  [s]ubdomain  [a]pi \n";
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
		
		$this->todo_index = $this->cache->get_todo_index( $this->projectid, $this->basecamp );
		
		if ( empty( $this->todo_index ) ) {
			echo "All todo lists for {$this->projectname} are complete.\n\n";
			$this->cache->set_project(null, $this->basecamp);
			$this->cache->set_todo_index(null, $this->basecamp);
			$this->run();
			exit;
		}
		
		echo "\n";
		foreach ($this->todo_index as $key => $p ) {
			echo "[$key] {$p['name']}\n";
		}
		echo "\n";
		
		$key = $this->input('Which todo list?');
		$this->active_todo_list = $this->todo_index[$key];

		$this->cache->set_active_todo_list( $this->project, $this->active_todo_list );
	
	}
	
	public function set_tasks() {
		//echo "\n\n";
		//print_r( $this->cache->get_active_todo_list( $this->project, $this->basecamp ) );
	}
	
	public function maybe_reload( $input ) {
		
		$input = strtolower( $input[0] );
		
		switch ( $input ) {
			case 'p':
			case 'projects':
				
				$this->cache->set_project_list(null, $this->basecamp);
				$this->cache->set_project(null, $this->basecamp);
				$this->project_list = $this->cache->set_project_list( null, $this->basecamp );

				exec('git config --unset basecamp.projectid');
				unset( $this->projectname, $this->projectid );

				break;
			
			case 't':
			case 'todo':
			
				$this->cache->set_todo_index( $this->projectid, null);
			
				break;
				
			case 's':
			case 'subdomain':
				exec( 'git config --global --unset basecamp.subdomain' );
				unset( $this->subdomain );
				break;
				
			case 'a':
			case 'api':
				exec( 'git config --global --unset basecamp.apitoken' );
				unset( $this->apitoken );
				break;
			default:
			
				// If input not listed, don't continue to run() below.
				return false;
			
				break;
		}
		
		$this->run();
		
	}
	
	public function input($prompt) {
		echo "$prompt \n";
		
		$input = trim(fgets(STDIN));
		
		$this->maybe_reload( $input );
		
		return $input;
	}
	
	public function clear($out = TRUE) {
	    $clearscreen = chr(27)."[H".chr(27)."[2J";
	    if ($out) print $clearscreen;
	    else return $clearscreen;
	}
}

$all_your = new Base(); // are belong to us

?>
