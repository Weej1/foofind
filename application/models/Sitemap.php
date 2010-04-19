<?php
/**
 * Model_Sitemap -
 *
 */
class Model_Sitemap extends Zend_Db_Table_Abstract
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
	public function getFile()
        {
		

		$table = new Model_Sitemap( );
		$select = $table->select()->setIntegrityCheck(false);
		$select->from(array('file' => 'ff_file'), array('file.*' ));
		$select->joinInner(array('filename' => 'ff_filename'), 'file.IdFile = filename.IdFile' , array('filename.*'));
                

                $select->where ( 'file.blocked = ?', 0 );
                $select->order( 'MaxSources DESC' );
                $select->limit( $count, 1);

		if ($table->fetchRow( $select )) {
		    $result = $table->fetchAll( $select )->toArray ();
		}

		return $result;

	}

        
	

}

