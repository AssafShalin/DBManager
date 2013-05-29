<?php
include_once dirname(__FILE__) . '/DB.php';
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class MapException extends Exception
{
    public function __construct($field,$key) {
        parent::__construct("Object map error: field '$field' or key '$key' is not set", "13");
    }
}
class FieldNotExistsException extends Exception
{
    public function __construct($field) {
        parent::__construct("Object error: '$field' is not set", "13");
    }
}
class BadInfoException extends Exception
{
    public function __construct() {
        parent::__construct("the 'getMapInfo' function is not implemented well", "13");
    }
}
class TableInfo
{
    /**
     *
     * @var string 
     */
    public $table_name;
    /**
     * Map of the table object_field_name => table_field_name
     * @var array 
     */
    public $map;
    
    public function validate() 
    {
        if(is_string($this->table_name)&&  is_array($this->map)) return true;
        return false;
    }
}
class MapInfo
{
    public function __construct($primary_key = null, $table_name=null,$table_map=null) {
        $this->table = new TableInfo();
        $this->table->table_name = $table_name;
        $this->table->map = $table_map;
        $this->primary_key = $primary_key;
    }
    /**
     *
     * @var array with key as the object 
     */
    public $primary_key;
    /**
     * @var TableInfo tables that are refered
     */
    public $table;
    public function validate()
    {
        
        if(is_array($this->primary_key)&& count($this->primary_key)==1 && $this->table instanceof TableInfo && $this->table->validate()==true)
            return true;
        else
            return false;
    }
    public function getPrimaryKeyKey()
    {
        return end(array_keys($this->primary_key));
    }
    public function getPrimaryKeyValue()
    {
        return $this->primary_key[$this->getPrimaryKeyKey()];
    }
}
/**
 * Description of Records
 *
 * @author Administrator
 */
abstract class Record {
    /**
     *
     * @var MapInfo 
     */
    private $mapInfo;
    protected function updateMapInfo(MapInfo $mapInfo)
    {
        $this->mapInfo = $mapInfo;
    }
    
    public function __construct() {
            $this->mapInfo = $this->createMapInfo();
    }
    protected abstract function createMapInfo();
    /**
     * a private function that will check the map info function, that is implimented by user
     * @return boolean
     */
    private function testMapInfo()
    {
        return $this->mapInfo->validate();
    }


    protected function createFromPrimaryKey()
    {
        if($this->testMapInfo()== false) throw new BadInfoException();
        $table_name = $this->mapInfo->table->table_name;
        $table_primary_key_key = $this->mapInfo->getPrimaryKeyKey();
        $table_primary_key_val = $this->mapInfo->getPrimaryKeyValue();
        
        $q = "SELECT * FROM `@param` WHERE `@param`='@param';";
        $params = array();
        $params[] = $table_name;
        $params[] = $table_primary_key_key;
        $params[] = $this->$table_primary_key_val;
        $db = DB::getInstance();
        $res = $db->doQueryWithArray($q, $params);
        if($res==false || $res->isEmpty()) return false;
        return $this->mapObjects($res->getObject());
    }
    protected function mapObjects(stdClass $obj)
    {
        if($this->testMapInfo()== false) throw new BadInfoException();
        if($obj==false) return false;
        $array = $this->mapInfo->table->map;
        foreach($array as $key => $field)
        {
            if(isset($obj->$field))
                $this->$key = $obj->$field;
            else
            {
                throw new MapException($field,$key);
            }
        }
        return true;
    }
    private function insert()
    {
        $info = $this->mapInfo;
        $table_name = $info->table->table_name;
        $field_array = $info->table->map;
        //add primary key to insert query
        $field_array[$info->getPrimaryKeyKey()] = $info->getPrimaryKeyValue();
        //insert header
        $insert = "INSERT INTO `@param` (";
        $values = " VALUES (";
        $params = array();
        //first param is the table name
        $params[] = $table_name;
        foreach ($field_array as $key => $field)
        {
            $insert .= '`' . $field .'`';
            $values .= "'@param'";
            //check if not the last iteration
            $params[] = $this->$key;
            if($key != end(array_keys($field_array))) {
                $insert .= ', '; 
                $values .= ', ';
            }
            else
            {
                $insert .= ")";
                $values .= ");";
            }
        }
        $query = $insert . $values;
        $db = DB::getInstance();
        $res = $db->doQueryWithArray($query,$params);
        //update primery key field
        $primary_key = $info->getPrimaryKeyKey();
        $this->$primary_key = $res->getInsertAutoIncrementID();
        
        if($res->getAffectedRows() > 0) return true;
        else return false;
    }
    private function update()
    {
        $mapInfo = $this->mapInfo;
        $table_name = $mapInfo->table->table_name;
        $field_array = $mapInfo->table->map;
        $primary_key_key = $mapInfo->getPrimaryKeyKey();
        $primary_key_val = $mapInfo->getPrimaryKeyValue();
        
        $query = "UPDATE `@param` SET ";
        $params = array();
        $params[] = $table_name;
        foreach ($field_array as $key => $field)
        {
            //primary key does not update
            if($field != $primary_key_val)
            {
                $value = $this->$key;
                $query.= "`$field` = '$value'";
                //check if not the last iteration
                $params[] = $this->$key;
                if($key != end(array_keys($field_array))) {
                    $query .= ', ';
                }
            }
        }
        $query .= " WHERE `$primary_key_val` = '{$this->$primary_key_key}';" ;
        $db = DB::getInstance();
        $res = $db->doQueryWithArray($query,$params);
        if($res->getAffectedRows() > 0) return true;
        else return false;
    }
    public function save()
    {
        if($this->testMapInfo()==false) throw new BadInfoException();
        if($this->isExists()) $this->update();
        else $this->insert();
    }
    private function isExists()
    {
        
        $mapInfo = $this->mapInfo;
        $table_name = $mapInfo->table->table_name;
        $primary_key_key = $mapInfo->getPrimaryKeyKey();
        $primary_key_val = $mapInfo->getPrimaryKeyValue();
        if($primary_key_val == false) return false;
        $params = array();
        $params[] = $table_name;
        $params[] = $primary_key_key;
        $params[] = $this->$primary_key_val;
        if(!isset($this->$primary_key_val)) return false;
        $q = "SELECT * FROM `@param` WHERE `@param` = '@param';";
        $db = DB::getInstance();
        $res = $db->doQueryWithArray($q, $params);
        if($res->isEmpty()) return false;
        return true;
        
    }
    
}

?>
