<?php
// First, set up the Cache
$cache = Zend_Cache::factory('Core', 'File',
                             array('automatic_serialization' => true),
                            array('cache_dir' => '/tmp'));

// Next, set the cache to be used with all table objects
Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);

class Model_Files extends Zend_Db_Table_Abstract
{
    public function getFile($id, $check_blocked = true)
    {
        $id = ( int )$id;
        $table = new ff_file();
        
        // By default, check blocked = 0, else use given criteria
        $where = ($check_blocked===true?"blocked=0":"blocked $check_blocked");
        $where .= " AND IdFile = $id";
        return $table->fetchRow ($where);
    }

    public function getFilenames($where)
    {
        $table = new ff_filename();
        return $table->fetchAll($where);
    }

    public function getSources($where)
    {
        $table = new ff_sources();
        return $table->fetchAll($where, "type");
    }

    public function getMetadata($where)
    {
        $table = new ff_metadata();
        return $table->fetchAll($where);
    }

    public function getFilename($idFilename)
    {
        $table = new ff_filename();
        return $table->fetchRow("IdFilename=$idFilename");
    }

    public function getFileFilename($idFile, $filename = NULL)
    {
        $table = new ff_filename();

        $select = $table->select()->where("IdFile=?", $idFile);

        // By default, select filename with more sources
        if ($filename==NULL) {
            $select = $select->order("MaxSources DESC");
        // If a filename is given, priorize that one if exists
        } else {
            $fn = $table->getDefaultAdapter()->quoteInto("'?'",$filename);
            $select = $select->order("(Filename='$fn') DESC, MaxSources DESC");
        }

        return $table->fetchRow($select);
    }
}

class ff_file extends Zend_Db_Table
{
    protected $_primary = 'IdFile';
}

class ff_filename extends Zend_Db_Table
{
    protected $_primary = array('IdFilename', 'IdFile');
}

class ff_sources extends Zend_Db_Table
{
    protected $_primary = array('Type', 'ShaUri');
}

class ff_metadata extends Zend_Db_Table
{
    protected $_primary = array('IdFile', 'CrcKey');
}

class ff_touched extends Zend_Db_Table
{
    protected $_primary = 'IdFile';
}