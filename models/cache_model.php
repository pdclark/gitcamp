<?php

class Cache_model
{

  public $cache;

  public function __construct()
  {
    $frontendOptions = array('lifetime' => NULL);
    $backendOptions = array('cache_dir' => 'tmp');
    $this->cache = Zend_Cache::factory('Output', 'File', $frontendOptions, $backendOptions);
  }

  public function get_project()
  {
    if( ($result = $this->cache->load('project')) === false ) 
    { 
      echo "No project set\n";     
    }
    else
    {
      echo $this->cache->load('project')."\n";
      return $this->cache->load('project')."\n";
    }
  }

  public function set_project($project)
  {
    $this->cache->save($project, 'project');
  }

}

?>
