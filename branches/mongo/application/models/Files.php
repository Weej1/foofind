<?php
class Model_Files 
{
    const SOURCE_GNUTELLA = 1;
    const SOURCE_ED2K = 2;
    const SOURCE_BITTORRENT = 3;
    const SOURCE_JAMENDO = 4;
    const SOURCE_TIGER = 5;
    const SOURCE_MD5 = 6;
    const SOURCE_BTH = 7;
    const SOURCE_HTTP = 8;
    const SOURCE_FTP = 9;
    const SOURCE_MEGAUPLOAD = 10;
    const SOURCE_RAPIDSHARE = 11;
    const SOURCE_MEGAVIDEO = 12;

    const CONTENT_AUDIO = 1;
    const CONTENT_VIDEO = 2;
    const CONTENT_BOOK = 3;
    const CONTENT_TORRENT = 4;
    const CONTENT_IMAGE = 5;
    const CONTENT_APPLICATION = 6;
    const CONTENT_ARCHIVE = 7;
    const CONTENT_ROM = 8;
    const CONTENT_DOCUMENT = 9;
    const CONTENT_SPREADSHEET = 10;
    const CONTENT_PRESENTATION = 11;


    static function ct2string($ct) {
        $ct2s = array(Model_Files::CONTENT_AUDIO => 'Audio', Model_Files::CONTENT_VIDEO => 'Video', Model_Files::CONTENT_BOOK => 'Document',
                        Model_Files::CONTENT_TORRENT => 'Archive', Model_Files::CONTENT_IMAGE => 'Image', Model_Files::CONTENT_APPLICATION => 'Software',
                     Model_Files::CONTENT_ARCHIVE => 'Archive', Model_Files::CONTENT_ROM => 'Software', Model_Files::CONTENT_DOCUMENT => 'Document',
                    Model_Files::CONTENT_SPREADSHEET => 'Document', Model_Files::CONTENT_PRESENTATION => 'Document');
        return $ct2s[$ct];
    }

    static function ct2ints($ct) {
        $ct2i = array('Audio' => array(Model_Files::CONTENT_AUDIO), 'Video' => array(Model_Files::CONTENT_VIDEO),
                           'Document' => array(Model_Files::CONTENT_BOOK, Model_Files::CONTENT_DOCUMENT, Model_Files::CONTENT_SPREADSHEET, Model_Files::CONTENT_PRESENTATION),
                           'Archive' => array(Model_Files::CONTENT_TORRENT, Model_Files::CONTENT_ARCHIVE, Model_Files::CONTENT_ROM),
                           'Image' => array(Model_Files::CONTENT_IMAGE), 'Software' => array(Model_Files::CONTENT_APPLICATION));
        return $ct2i[$ct];
    }

    static function src2ints($src) {
        $src2i = array('s' => array(12),
                        'w' => array(4,8,10,11),
                        'f' => array(9),
                        't' => array(3,107),
                        'g' => array(1,5,6),
                        'e' => array(2));
        return $src2i[$src];
    }

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
    }

    public function getFiles($uris)
    {
        $cursor = $this->collection->find( array("_id" => array('$in' => $uris ) ) );
        foreach ($cursor as $file) {
            $files []= $file;
        }
        return $files;
    }

    public function getFile($uri)
    {
        //TODO  check blocked = 1
        $id = new MongoId($uri);
        $file = $this->collection->findOne( array("_id" =>$id) );
        return $file;
    }
    
    public function getLastFilesIndexed( $limit )
    {
        $cursor =  $this->collection->find(  )->sort( array( "fs"  => -1) )->limit( (int)$limit) ;

        foreach ($cursor as $file) {
            $files []= $file;
        }
        return $files;
    }
}
