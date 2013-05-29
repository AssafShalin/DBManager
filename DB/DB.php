<?php
 include_once dirname(__FILE__) . '/../Config/DB_Info.php';
class SQLException extends ErrorException
{
    public function __construct($code, $message) {
        parent::__construct($message, $code, "0");
    }
}
 class DB {
//-------------------------------------------------------------------------------------------
//This is DB connection Class
//Database info is in DB_Info.php
//Constructor does the link.
//use doQuery function to run a query on the mySQL server.
//-------------------------------------------------------------------------------------------

    //DB Link.
    private $link;
    
    //static singleton
    
    private static $instance;
    
    /**
     * get a shared singleton instance of db object
     * @return DB 
     */
    public static function getInstance()
    {
        if(DB::$instance==NULL) DB::$instance = new DB();
        return DB::$instance;
    }
    
    private function __construct() {
        //Load DB info
        $dbinfo = new DB_Info();
        
        //Make connection
        $this->link= mysql_connect($dbinfo->getHost(), $dbinfo->getUsr(),$dbinfo->getPass()) or die(mysql_error());
       //Choose DB.
        mysql_select_db($dbinfo->getDbname(), $this->link) or die(mysql_error());
    } 
    /**
     * do a mysql query
     * @param string $sql
     * @return DBResults
     */
    private function doDBQuery($sql) {
        //do query.
        mysql_query("SET NAMES 'utf8'");
        $result = mysql_query($sql,$this->link);
        if($result==FALSE) throw new SQLException(mysql_errno(),  mysql_error());
        $result = new DBResults($result);
        return $result;
    }
       /**
     * will preform a query on the database. all variables must be represended in the query as '@param` 
     * and be specified later, as a function parameter
     * @param string $query the query itself
     * @param array an array of prams
     * @return DBResults
     */
    public function doQueryWithArray($query, array $params=array())
    {
        //prepare query
        for($i=0;$i<count($params);$i++)
        {
                $param = $params[$i];
                //do some processing on param
                $param = $this->validateString($param);
                //adding to the quetry
                $query = preg_replace('/@param/', $param, $query, 1);

        }
        return $this->doDBQuery($query);
    }
    
    /**
     * will preform a query on the database. all variables must be represended in the query as '@param` 
     * and be specified later, as a function parameter
     * @param string $query the query itself
     * @param string unlimited pramas
     * @return DBResults
     */
    public function doQuery($query, $params=null)
    {
        //prepare query
        $args = func_get_args();
        if($args<1) return "";
        for($i=1;$i<count($args);$i++)
        {
                $param = $args[$i];
                //do some processing on param
                $param = $this->validateString($param);
                //adding to the quetry
                $query = preg_replace('/@param/', $param, $query, 1);

        }
        return $this->doDBQuery($query);
    }

    
    //------------------------------------------------------------
    //Strips any string from any escapments (SQL-I, NOT XSS)
    //@param - any string
    //@return - striped string.
    //----------------------------------------------------------------
    private function validateString($data) {
        $data = stripcslashes($data);
        $data = mysql_real_escape_string($data);
        $data = trim($data);
        return $data;
    }
    
    
    public function __destruct() {
        #mysql_close();
    }
}


class DBResults {
    private $resource;
    function __construct($resource) {
        $this->resource = $resource;
    }

    /**
     * returns the num of rows in the current results
     * @return int 
     */
    public function getCount()
    {
        return mysql_num_rows($this->resource);
    }
    /**
     * will return an assosiative array that represents the current row of a table in the current results
     * @return array
     */
    public function getArray()
    {
        return mysql_fetch_array($this->resource);
    }
    /**
     *  will return an object of the current row in the table
     * @return stdClass
     */
    public function getObject()
    {
        $arr = $this->getArray();
        if($arr == false) return false;
        $obj = new stdClass;
        foreach($arr as $key=>$data)
        {
            if(!is_numeric($key))
                $obj->$key = $data;
        }
        return $obj;
    }
    /**
     * will return if the current results are empty
     * @return bool
     */
    public function isEmpty()
    {
        return(($this->getCount()==0)?TRUE:FALSE);
    }
    /**
     * will return the auto increment value of an insert query 
     * @return int the value
     */
    public function getInsertAutoIncrementID()
    {
        return mysql_insert_id();
    }
    /**
     * will return the number of affected rows
     * @return int the number of affected rows after a query 
     */
    public function getAffectedRows()
    {
       return mysql_affected_rows();
    }
    public function getAllRecordsAsArrayOfArrays()
    {
        $array = array();
        while(($record = $this->getArray())!= FALSE)
        {
            $array[] = $record;
        }
        return $array;
    }
    public function getAllRecordsAsObjectArray()
    {
        $array = array();
        while($record = $this->getObject())
        {
            $array[] = $record;
        }
        return $array;
    }
        
}
?>