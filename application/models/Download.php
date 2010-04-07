<?php


/**
 * Model_Download - a model class representing a file
 *
 *
 */
class Model_Download extends Zend_Db_Table_Abstract
{
	protected $_name = 'ff_filename';
	protected $_primary = 'IdFile';
	
	/** Model_Table_Page */
	protected $_table;




	/**
	 * Fetch a file (to the download view)
	 *
	 * @param  int $id
	 * @return array
	 */
	public function getFile($id)
        {
		$id = ( int )$id;

		$table = new Model_Download( );
		$select = $table->select()->setIntegrityCheck(false);
		$select->from(array('file' => 'ff_file'), array('file.*' ));
		$select->joinInner(array('filename' => 'ff_filename'), 'file.IdFile = filename.IdFile' , array('filename.*'));
                

		$select->where ( 'file.idFile = ?', $id );
                $select->where ( 'file.blocked = ?', 0 );
                //$select->order('MaxSources');

                $select->limit( $count, 1);

		if ($table->fetchRow( $select )) {
		    $result = $table->fetchRow( $select )->toArray ();
		}

		return $result;

	}

        
	public  function getMetadata($id)
        {
                     $id = ( int ) $id;

                    if ($id){

                        $metadata = new Zend_Db_Table('ff_metadata');
                        $query = "SELECT ff_metadata.KeyMD , ff_metadata.ValueMD  FROM ff_metadata WHERE ff_metadata.IdFile = ".$id;
                        $result = $metadata->getAdapter()->query($query)->fetchAll();
                    }

		return $result;

        }


        public  function getSources($id)
        {
                     $id = ( int ) $id;

                    if ($id){

                    $sources = new Zend_Db_Table('ff_sources');
                    $query = "SELECT ff_sources.*   FROM ff_sources WHERE ff_sources.IdFile = ".$id;
                    $result = $sources->getAdapter()->query($query)->fetchAll();

                    }

		return $result;


        }


}

