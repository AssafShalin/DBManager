<?php
/**
 * Database Connection info.
 *
 * @author Assaf
 */
class DB_Info {
    
    //DB DATA, stays static
    private $host='localhost';
    private $usr='';
    private $pass='x';
    private $dbname='';
    
    public function getHost() {
        return $this->host;
    }
    public function getUsr() {
        return $this->usr;
    }
    public function getPass() {
        return $this->pass;
    }
    public function getDbname() {
        return $this->dbname;
    }

}

?>
