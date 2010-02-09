<?php

class Model_Search extends Zend_Db_Table_Abstract 
{

    class Filename extends Zend_Db_Table_Abstract  {
        protected $_name = 'ff_filename';
        protected $_primary = "IdFilename";
	
    };

    public function fetchFilenames($ids) 
    {
	$table = new Filename();
        return $table->fetchAll('IdFile in (?)', $ids);
    }



}

/*
class Model_Metadata extends Zend_Db_Table_Abstract {
   public function fetchMetadata($id) {
          $id = ( int ) $id;
          $table = new Model_Search ( );
          $select = $table->select ()->where ( 'IdFile = ?', $id );
          $result = $table->fetchRow ( $select )->findDependentRowset('Metadata');
          return $result;
   }






 public function fetchSources($id) {
                $id = ( int ) $id;

                $table = new Model_Search ( );
                $select = $table->select ()->where ( 'IdFile = ?', $id );

                $result = $table->fetchRow ( $select )->findDependentRowset('Sources');

                return $result;

        }

  
}



class Filename extends Zend_Db_Table_Abstract  {
        protected $_name = 'ff_filename';

        protected $_referenceMap    = array(
    'Filename' => array(
        'columns'           => array('IdFile'),
        'refTableClass'     => 'Model_Search',
        'refColumns'        => array('IdFile')
    )
);

}


class Metadata extends Zend_Db_Table_Abstract  {
        protected $_name = 'ff_metadata';

        protected $_referenceMap    = array(
    'Metadata' => array(
        'columns'           => array('IdFile'),
        'refTableClass'     => 'Model_Search',
        'refColumns'        => array('IdFile')
    )
);


}

class Sources extends Zend_Db_Table_Abstract  {
        protected $_name = 'ff_sources';

        protected $_referenceMap    = array(
    'Sources' => array(
        'columns'           => array('IdFile'),
        'refTableClass'     => 'Model_Search',
        'refColumns'        => array('IdFile')
    )
);


}
*/
