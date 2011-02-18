<?php

Class Bc 
{

  public function list_projects()
  {
    $call = 'listprojects';
    $connect = new Api();
    $data = $connect->api_connect($call);
    foreach($data as $row)
    {
      echo (string) $row->id[0] . ":" . $row->name[0]  . "\n";
    }
  }

}

?>
