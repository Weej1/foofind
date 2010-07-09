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
        $oBackend = new Zend_Cache_Backend_Memcached(
                        array(
                                'servers' => array( array(
                                        'host' => '127.0.0.1',
                                        'port' => '11211'
                                ) ),
                                'compression' => true
                ) );

        $oFrontend = new Zend_Cache_Core(
                array(
                        'caching' => true,
                        'lifetime' => 3600,
                        'cache_id_prefix' => 'foofy_search',
                        'automatic_serialization' => true,

                ) );

        // build a caching object
        $this->oCache = Zend_Cache::factory( $oFrontend, $oBackend );

        $connection = new Mongo("mongo.files.foofind.com:27017");
        $this->db = $connection->foofind;
    }

    public function getServers()
    {
        $key = "servers";
        $existsCache = $this->oCache->test($key);
        if  ( $existsCache  ) {
            $servers = $this->oCache->load($key);
        } else {
            $cursor = $this->db->server->find()->sort(array('lt'=>-1));
            $servers = array();
            foreach ($cursor as $server) {
                $servers[$server['_id']] = $server;
            }
            $this->oCache->save( $servers, $key );
        }
        return $servers;
    }

    public function countFiles()
    {
        $count = $this->db->server->group(array(), array("c"=>0), "function(obj,prev) { prev.c += obj.c; }");
        return $count["retval"][0]['c'];
    }

    public function getFiles($uris)
    {
        $files = array();
        
        $cursor = $this->db->indir->find( array("_id" => array('$in' => $uris ) ) );
        $querys = array();
        foreach ($cursor as $ifile) {
            $s = $ifile['s'];
            if (!array_key_exists($s, $querys)) $querys[$s] = array();

            if (array_key_exists('t', $ifile))
                $querys[$s][]=new MongoId($ifile['t']);
            else
                $querys[$s][]=new MongoId($ifile['_id']);

        }

        $servers = $this->getServers();
        foreach ($querys as $s=>$suris) {
            $server = $servers[$s];
            $conn = new Mongo("{$server['ip']}:{$server['p']}");
            $cursor = $conn->foofind->foo->find(array("_id" => array('$in' => $suris ) ) );
            foreach ($cursor as $file) {
                $files[$file['_id']->__toString()] = $file;
            }
        }
        return $files;
    }

    public function getFile($uri)
    {
        $id = new MongoId($uri);
        $ifile = $this->db->indir->findOne( array("_id" =>$id) );
        $s = $ifile['s'];
        $servers = $this->getServers();
        $server = $servers[$s];
        $conn = new Mongo("{$server['ip']}:{$server['p']}");
        return $conn->foofind->foo->findOne(array("_id" =>$id ) );
    }
    
    public function getLastFilesIndexed( $limit )
    {
        $servers = $this->getServers();
        $server = current($servers);

        $conn = new Mongo("{$server['ip']}:{$server['p']}");
        $cursor = $conn->foofind->foo->find()->sort(array('$natural' => -1))->limit($limit);
        foreach ($cursor as $file) {
            $files []= $file;
        }
        return $files;
    }
}
