<?php

Class DB
{

  public function db_connect()
  {
    $config = new Zend_Config_Ini(APPLICATION_PATH . '/config/config.ini', 'account');
    $config_db = new Zend_Config(
    array(
      'database' => array(
        'adapter' => 'Mysqli',
          'params'  => array(
            'host'     => $config->mysql->host,
            'dbname'   => $config->mysql->database,
            'username' => $config->mysql->username,
            'password' => $config->mysql->password,
          )
        )
      )
    );
    $init_db = Zend_Db::factory($config_db->database); 
    return $init_db;
  }

}

?>
