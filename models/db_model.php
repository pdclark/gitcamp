<?php

class Db_model
{

  public function save_db()
  {
    // $connect = $this->db_connect();  
    $sql = 'SELECT * FROM cs_commits';
    $result = $connect->fetchAll($sql, 2);
    print_r($result);
  }

}

?>
