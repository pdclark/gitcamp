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
	
	// Loaded from git & Basecamp
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
		
		// Local app files
		include_once(APPLICATION_PATH . '/lib/api_connector.php');
		include_once(APPLICATION_PATH . '/lib/basecamp.php');
		include_once(APPLICATION_PATH . '/lib/cache_model.php');
		
		// CLI arguments
		$this->opts = new Zend_Console_Getopt('abp:');
		if ( empty($this->args) ) { $this->args = $this->opts->getRemainingArgs(); }
		
		$this->script_name = 'base';
		
		$this->run();
	}
	
	public function config() {
		// Called at start of $this->run()
		
		$this->clear();
		
		// Are we in a git repo?
		unset($return);
		exec('git status', $return, $exit);
		if ( $exit !== 0 ) {
			exit("Please run $this->script_name inside a git repository.\n");
		}
		
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
		
		
		// Connect to Basecamp
		$this->basecamp = new Basecamp( $this->subdomain, $this->apitoken );
		$this->cache = new Cache_model();
		
		// Project list
		$this->project_list = $this->cache->get_project_list( $this->basecamp );
			
		// Project Name & ID
		unset($return);
		exec('git config --get basecamp.projectid', $return);
		$this->projectid = $return[0];
		
		if (empty( $this->projectid ) ) {
		
			$this->header();
			
			echo "\n";
			foreach ($this->project_list as $key => $p ) {
				echo "[$key] {$p['name']}\n";
			}
			echo "\n";
			
			$key = $this->input('Which project is this git repo for?');
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
		
		$this->header();
		
	}
 
	public function run() {

		$this->config();
		
		switch ( strtolower( $this->args[0] ) ) {
			case 'complete_tasks':
			case 'done':
			case 'mark':
			case 't':
				$this->complete_tasks();
				break;
			
			case 'hooks':
				$this->hooks();
				break;
		}
		
		$this->active_todo_list = $this->cache->get_active_todo_list( $this->project, $this->basecamp );
		$this->get_todo_index();
		$this->tasks = $this->cache->get_tasks( $this->projectid, $this->active_todo_list['id'], $this->basecamp );
		
		$this->output_tasks();
		
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
	
	public function output_tasks() {
		// Load Tasks
		foreach ($this->tasks as $key => $task ) {
			extract($task);
			$out .= "$content $id\n";
		}
		// exec('echo "'.$out.'" | mate;');
	
		exec('echo "'.$out.'" | git commit --edit --file -');
	}
	
	public function hooks() {
		
		exec( 'if [ -d ./.git ]; then echo 1; else echo 0; fi', $is_git_root );
		if ( $is_git_root[0] == '0' ) {
			$this->clear();
			echo "Please run $this->script_name hooks from the root of your git directory.\n";
			exit;
		}else {
			$this->clear();
			exec('echo "base;" >> .git/hooks/pre-commit; chmod 755 .git/hooks/pre-commit;');
			echo "Pre-commit hook added.\n";
			exec('echo "base complete_tasks;" >> .git/hooks/post-commit; chmod 755 .git/hooks/post-commit;');
			echo "Post-commit hook added.\n";
			exit;
		}
		
	}
	
	public function complete_tasks() {
		exec( 'git log -n 1 --format=format:"%B"', $commit_message );
		
		foreach ( $commit_message as $line ) {
			preg_match('/ ([0-9]{8})$/i', $line, $match);
			if (!empty($match[1])) {
				$tasks[] = $match[1];
			}
		}
		
		$this->clear();
		
		if (empty($tasks)) {
			echo "No tasks found in last commit message.\n";
			exit;	
		}
		
		echo "Marking tasks complete... \n";
		foreach ($tasks as $task_id) {
			echo "$task_id... ";
			if ( $this->basecamp->complete_task( $task_id ) ) {
				echo "Done. \n";
			}else {
				echo "Failed. \n";
			}
		}

		exit;
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
	
	public function get_todo_index() {
		
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
		
		$key = $this->input('Commit which todo list?');
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
