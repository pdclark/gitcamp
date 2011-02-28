<?php

Class Basecamp 
{

  public $connect;

  public function __construct()
  {
    $this->connect = new Api_connector();
  }

  public function list_projects()
  {
    $call = '/projects.xml';
    $data = $this->connect->api_connect($call);
    if(is_object($data))
    {
      foreach($data as $row)
      {
        echo (string) $row->id[0] . ":" . $row->name[0]  . "\n";
      }
    }
  }

  public function project_get_all_lists($project)
  {
    $call = '/projects/'.$project.'/todo_lists.xml';
    $data = $this->connect->api_connect($call);
    if(is_object($data))
    {
      foreach($data as $row)
      {
        echo (string) $row->id[0] . ":" . $row->name[0]  . "\n";
      }
    }
  }
}

?>
