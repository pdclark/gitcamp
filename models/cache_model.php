<?php

class Cache_model
{

  public function get_project($project)
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

?>
