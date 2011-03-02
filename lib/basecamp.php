<?php

Class Basecamp 
{

	public $connect;

	public function __construct()
	{
		$this->connect = new Api_connector();
	}

	public function get_project_list()
	{
		$list = array();
		$call = '/projects.xml';
		$data = $this->connect->api_connect($call);
		if(is_object($data)) {
			
			foreach($data as $row) {
				
				if ($row->status == 'active') {
					
					$list[] = array(
						'name'=> (string) $row->name[0],
						'id'=> (int) $row->id[0],
					);
					
				};
			}
		}

		return $list;
		
		
	}

	public function get_todo_index($project_id)
	{
		$list = array();
		$call = '/projects/'.$project_id.'/todo_lists.xml';
		$data = $this->connect->api_connect($call);

		if(is_object($data)) {
			
			foreach($data as $row) {
				
				if ( (string)$row->complete[0] == 'false') {
					
					$list[] = array(
						'name'=> (string) $row->name[0],
						'id'=> (int) $row->id[0],
						'position' => (int) $row->position[0],
					);
					
				}
			}
		}
		return $list;
	}

	public function get_active_todo_items($project, $active_todo) {
		$list = $active_todo['id'];
		print_r($active_todo);
		echo $call;
		$call = '/projects/'.$project.'/todo_lists/'.$list.'.xml';
		$data = $this->connect->api_connect($call);
		if(is_object($data))
		{
			foreach($data->{'todo-items'}[0] as $row)
			{
		// print_r($row);
		if ($row->completed == 'false') {			
			echo (string) $row->id . ":" . $row->content	. "\n";
		}
			}
		}
	}
}

?>
