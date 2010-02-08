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

    protected $_dependentTables = array('ff_filename' ,'ff_metadata', 'ff_sources');


        /** Model_Table_Page */
        protected $_table;



   public function fetchMetadata($id) {
          $id = ( int ) $id;
          $table = new Model_Search ( );
          $select = $table->select ()->where ( 'IdFile = ?', $id );
          $result = $table->fetchRow ( $select )->findDependentRowset('Metadata');
          return $result;
   }



   public function fetchFilenames($id) {
                $id = ( int ) $id;

                $table = new Model_Search ( );
                $select = $table->select ()->where ( 'IdFile = ?', $id );

                $result = $table->fetchRow ( $select )->findDependentRowset('Filename');

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
