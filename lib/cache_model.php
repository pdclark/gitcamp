<?php

class Cache_model
{

	public $cache;

	public function __construct()
	{
		$frontendOptions = array('lifetime' => NULL);
		$backendOptions = array('cache_dir' => APPLICATION_PATH.'/tmp');
		$this->cache = Zend_Cache::factory('Output', 'File', $frontendOptions, $backendOptions);
	}

	public function get_project($basecamp)
	{
		$result = unserialize($this->cache->load('project'));
		
		if ( !empty($result) ) {
			return $result;
		}else {
			return false;
		}
		
	}

	public function set_project($project)
	{
		return $this->cache->save(serialize($project), 'project');
	}

	public function get_project_list($basecamp)
	{
		$result = unserialize($this->cache->load('project_list'));
		if ( !empty($result) ) {
			return $result;
		}else {
			echo "Loading projects... \n\n";
			$project_list = $basecamp->get_project_list();
			$this->set_project_list($project_list);
			return $project_list;
		}
	}
	
	public function set_project_list($project_list)
	{
		$this->cache->save( serialize($project_list), 'project_list');
	}
	
	public function get_todo_index($project_id, $basecamp)
	{
		$result = unserialize($this->cache->load( 'todo_list_'.$project_id ));
		if ( !empty($result) ) {
			return $result;
		}else {
			echo "Loading todo lists... \n\n";
			$list = $basecamp->get_todo_index( $project_id );

			$this->set_todo_index($project_id, $list);

			return $list;
		}
	}
	
	public function set_todo_index($project_id, $list)
	{
		$this->cache->save( serialize($list), 'todo_list_'.$project_id );
	}
	
	public function get_active_todo_list($project_id, $basecamp)
	{
		$result = unserialize($this->cache->load( 'active_todo_'.$project_id ));
		if ( !empty($result) ) {
			return $result;
		}else {
			return false;
			// echo 'Do something here...';
		}
	}
	
	public function set_active_todo_list($project_id, $active_todo)
	{
		$this->cache->save( serialize($active_todo), 'active_todo_'.$project_id );
	}
	
	
	public function get_tasks($project_id, $active_todo_id, $basecamp)
	{
		$result = unserialize($this->cache->load( 'tasks_'.$project_id.$active_todo_id ));
		if ( !empty($result) ) {
			return $result;
		}else {
			echo "Loading tasks... \n\n";
			$tasks = $basecamp->get_tasks( $project_id, $active_todo_id );

			$this->set_tasks($project_id, $active_todo_id, $tasks);

			return $tasks;
		}
	}
	
	public function set_tasks($project_id, $active_todo_id, $tasks)
	{
		$this->cache->save( serialize($tasks), 'tasks_'.$project_id.$active_todo_id );
	}
	
	
	

}

?>
