<?php 
class Test extends Record{
    public $id;
    public $name;
}


//will create a new test record, and store AI key in id (if AI)
$test = Test::create();
$test->name = "test";
$test->save();

//will fetch test obj with pkey '1'
$test = Test::fetch(1);

//return an array of tests
$test = Test::fetchWhere('$obj->name == "test"');

//return an array of tests
$test = Test::fetchAll();
?>