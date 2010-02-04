<?php
/**
 * Nolotiro_User - a model class representing a single user
 *
 * This is the DbTable class for the users table.
 *
 * */
class Model_Search extends Zend_Db_Table_Abstract {

    protected $_name = 'ff_file';
    protected $_primary = "IdFile";
    //protected $_dependentTables = array('comments');



    public function fetchFiles() { //just to test , gets allll the dataaa, dont use anymore
        $select = $this->select();
        return $this->fetchAll($select)->toArray();
    }





    public function fetchSearch(){


    }

}

