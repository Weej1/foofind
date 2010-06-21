<?php
class Model_Files 
{

    function  __construct()
    {  
       $connection = new Mongo("mongo.files.foofind.com:27017");
       $db = $connection->foofind;
       $this->collection = $db->foo;
    }


    public function countFiles()
    {
        return $this->collection->count( );
    }


    public function getFile($uri)
    {
        //TODO  check blocked = 1
       $file = $this->collection->findOne( array("src.url" =>$uri) );
       return $file;

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

    

    public function getLastFilesIndexed( $limit )
    {
      $cursor =  $this->collection->find( )->limit( (int)$limit)->sort(  array( "_id"  => -1) ) ;

      foreach ($cursor as $file) {
            $files[] = $file;
        }
      
        return $files;

      }


}
