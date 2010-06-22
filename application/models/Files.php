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
        $count = 0;
        for ($port=10001; $port<10004; $port++)
        {
            $filter = array( 'fs' => array('$gt' => new MongoDate(strtotime("1999-01-01 00:00:00")) ));
            $connection = new Mongo("mongo.files.foofind.com:$port");
            $count += $connection->foofind->foo->count($filter );
        }
        return $count;
       // return $this->collection->count( array( "fs" ));
       // $filter = array( 'fs' => array('$gt' => 0) );

    }


    public function getFile($uri)
    {
        //TODO  check blocked = 1

        //reverse id

        
        //$uri = base64_decode(  $uri  );
       $uri = str_replace('-', '/', $uri);
       $uri = str_replace('!', '+', $uri);
        
        $uri = base64_decode(  $uri   );


        $uri = bin2hex($uri);
       

         var_dump($uri);
//      die();

         

       

        $id = new MongoId($uri);
       $file = $this->collection->findOne( array("_id" =>$id) );
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


      $filter = array( 't' => array('$exists' => false) );
      //$cursor =  $this->collection->find( $filter  )->limit( (int)$limit)->sort(  array( "_id"  => -1) ) ;
      $cursor =  $this->collection->find( $filter  )->limit( (int)$limit)->sort(  array( "fs"  => -1) ) ;


      foreach ($cursor as $file) {
            $files[] = $file;
        }
      
        return $files;

      }


}
