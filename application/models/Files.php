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

    static function ext2ct($ext) {
        $exts = array("264"=>2, "3d"=>5, "3ds"=>5, "3dr"=>5, "3g2"=>2, "3gp"=>2, "7z"=>7, "7zip"=>7,
        "aac"=>1, "abr"=>5, "abw"=>9, "ace"=>7, "act"=>5, "aeh"=>3, "afp"=>9, "ai"=>5, "aif"=>1,
        "aifc"=>1, "aiff"=>1, "air"=>6, "alx"=>6, "alz"=>7, "amr"=>1, "ani"=>5, "ans"=>9, "ape"=>6,
        "apk"=>6, "aplibrary"=>5, "app"=>6, "arc"=>7, "arj"=>7, "art"=>5, "arw"=>5, "asf"=>2,
        "asx"=>2, "at3"=>7, "au"=>1, "aup"=>1, "avi"=>2, "awg"=>5, "aww"=>9, "azw"=>3, "bat"=>6,
        "big"=>7, "bik"=>2, "bin"=>7, "bke"=>7, "bkf"=>7, "blp"=>5, "bmp"=>5, "bw"=>5, "bzip2"=>7,
        "cab"=>7, "caf"=>1, "cbr"=>3, "cbz"=>3, "ccd"=>8, "cda"=>1, "cdr"=>5, "cgm"=>5, "chm"=>3,
        "cit"=>5, "class"=>6, "cmx"=>5, "cod"=>6, "com"=>6, "cpt"=>5, "cr2"=>5, "crw"=>5, "csv"=>10,
        "cut"=>5, "cwk"=>9, "daa"=>8, "dao"=>8, "dat"=>2, "dcr"=>5, "dds"=>7, "deb"=>7, "dib"=>5,
        "divx"=>2, "djvu"=>3, "dll"=>6, "dmg"=>8, "dng"=>5, "dnl"=>3, "doc"=>9, "docm"=>9, "docx"=>9,
        "dot"=>9, "dotm"=>9, "dotx"=>9, "drw"=>5, "dwg"=>5, "dxf"=>5, "ecab"=>7, "eea"=>7, "egt"=>5,
        "emf"=>5, "emz"=>5, "eps"=>9, "epub"=>3, "erf"=>5, "ess"=>7, "exe"=>6, "exif"=>5, "fax"=>9,
        "fb2"=>3, "fff"=>5, "fla"=>6, "flac"=>1, "flv"=>2, "flw"=>2, "fpx"=>5, "ftm"=>9, "ftx"=>9,
        "gadget"=>6, "gho"=>7, "gif"=>5, "gz"=>7, "gzip"=>7, "hqx"=>7, "htm"=>9, "html"=>9, "hwp"=>9,
        "ibm"=>5, "icb"=>5, "ico"=>5, "icon"=>5, "icns"=>5, "iff"=>5, "ilbm"=>5, "img"=>8, "ind"=>5,
        "info"=>9, "int"=>5, "ipa"=>6, "iso"=>8, "isz"=>8, "j2k"=>5, "jar"=>6, "jng"=>5, "jpeg"=>5,
        "jp2"=>5, "jpg"=>5, "kdc"=>5, "keynote"=>11, "kml"=>9, "la"=>1, "lbr"=>7, "lha"=>7, "lit"=>3,
        "lqr"=>7, "lrf"=>3, "lrx"=>3, "lwp"=>9, "lzo"=>7, "lzx"=>7, "m2ts"=>2, "m4a"=>1, "m4b"=>1,
        "m4p"=>1, "m4v"=>2, "mcw"=>9, "mdf"=>8, "mds"=>8, "mef"=>5, "mht"=>9, "midi"=>1, "mkv"=>2,
        "mobi"=>3, "mod"=>1, "mos"=>5, "mov"=>2, "mp+"=>1, "mp2"=>1, "mp3"=>1, "mp4"=>2, "mpa"=>1,
        "mpc"=>1, "mpe"=>2, "mpeg"=>2, "mpg"=>2, "mpp"=>1, "mrw"=>5, "msi"=>6, "nb"=>9, "nbp"=>9,
        "nds"=>6, "nef"=>5, "nes"=>6, "nrg"=>8, "nsv"=>2, "numbers"=>10, "ocx"=>6, "odg"=>5,
        "odp"=>11, "ods"=>10, "odt"=>9, "ogg"=>1, "ogm"=>2, "ogv"=>2, "opf"=>3, "orf"=>5, "otp"=>11,
        "ots"=>10, "ott"=>9, "pages"=>9, "pak"=>7, "pac"=>1, "pap"=>9, "par"=>7, "par2"=>7, "pbm"=>5,
        "pcd"=>5, "pcf"=>5, "pcm"=>1, "pct"=>5, "pcx"=>5, "pdb"=>3, "pdd"=>5, "pdf"=>3, "pdn"=>5,
        "pef"=>5, "pgm"=>5, "pk4"=>7, "pkg"=>7, "pix"=>5, "pnm"=>5, "png"=>5, "potx"=>11, "ppm"=>5,
        "pps"=>11, "ppsm"=>11, "ppsx"=>11, "ppt"=>11, "pptm"=>11, "pptx"=>11, "prc"=>3, "prg"=>6,
        "ps"=>9, "psb"=>5, "psd"=>5, "psp"=>5, "ptx"=>5, "px"=>5, "pxr"=>5, "qfx"=>5, "r3d"=>5,
        "ra"=>1, "raf"=>5, "rar"=>7, "raw"=>5, "rgb"=>5, "rgo"=>3, "rka"=>1, "rm"=>2, "rma"=>1,
        "rom"=>8, "rtf"=>9, "sav"=>6, "scn"=>6, "scr"=>6, "sct"=>5, "scx"=>6, "sdw"=>9, "sea"=>7,
        "sgi"=>5, "shn"=>1, "shp"=>5, "sisx"=>6, "sit"=>7, "sitx"=>7, "skp"=>5, "snd"=>1, "sng"=>1,
        "sr2"=>5, "srf"=>5, "srt"=>9, "sti"=>9, "stw"=>9, "sub"=>9, "svg"=>5, "svi"=>2, "swf"=>6,
        "sxc"=>10, "sxi"=>9, "sxw"=>9, "tao"=>8, "tar"=>7, "targa"=>5, "tb"=>7, "tex"=>9, "text"=>9,
        "tga"=>5, "tgz"=>7, "theme"=>6, "themepack"=>6, "thm"=>5, "thmx"=>11, "tib"=>7, "tif"=>5,
        "tiff"=>5, "toast"=>8, "torrent"=>4, "tr2"=>3, "tr3"=>3, "txt"=>9, "uha"=>7, "uif"=>8,
        "uoml"=>9, "vbs"=>6, "vcd"=>8, "vda"=>5, "viff"=>5, "vob"=>2, "vsa"=>7, "vst"=>5, "wav"=>1,
        "webarchive"=>9, "wma"=>1, "wmf"=>5, "wmv"=>2, "wol"=>3, "wpd"=>9, "wps"=>9, "wpt"=>9,
        "wrap"=>2, "wrf"=>9, "wri"=>9, "wv"=>1, "x3f"=>5, "xar"=>5, "xbm"=>5, "xcf"=>5, "xls"=>10,
        "xlsm"=>10, "xlsx"=>10, "xdiv"=>2, "xhtml"=>9, "xls"=>9, "xml"=>9, "xpi"=>6, "xpm"=>5,
        "xps"=>9, "yuv"=>5, "z"=>7, "zip"=>7, "zipx"=>7, "zix"=>7, "zoo"=>7 );
        return $exts[$ext];
    }

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

    private function prepareConnections($main = true, $oldids = false, $datas = false)
    {
        if (($main || $datas) && !isset($this->db_main))
        {
            $db = Zend_Registry::get("db_main");
            if (!$db->connected) $db->connect();
            $this->db_main = $db->foofind;
        }

        if ($oldids && !isset($this->db_oldids))
        {
            $db = Zend_Registry::get("db_oldids");
            if (!$db->connected) $db->connect();
            $this->db_oldids = $db->foofind;
        }

        if ($datas && !isset($this->db_data))
        {
            $this->db_data = array();

            $cache = Zend_Registry::get("cache");
            $key = "svs";
            $existsCache = $cache->test($key);
            if  ( $existsCache  ) {
                $this->servers = $cache->load($key);
            } else {
                $cursor = $this->db_main->server->find()->sort(array('lt'=>-1));
                $this->servers = array();
                foreach ($cursor as $server) {
                    if (!isset($this->servers[0])) $this->servers[0] = $server['_id']; // current server
                    $this->servers[$server['_id']] = $server;
                }
                unset ($cursor);
                $cache->save( $this->servers, $key );
            }
            foreach ($this->servers as $s=>$data)
            {
                $this->db_data[$s] = new Mongo("{$data['ip']}:{$data['p']}", array("connect"=>false));
            }
        }
    }

    public function getFileUrlFromID($id)
    {
        $this->prepareConnections(false, true);
        $res = $this->db_oldids->foofind->foo->findOne(array('i'=>(int)$id), array('_id'=>1));

        if ($res == NULL)
            $ret = NULL;
        else
            $ret = $res['_id'];
        return $ret;
    }

    public function countFiles()
    {
        $this->prepareConnections();
        $count = $this->db_main->server->group(array(), array("c"=>0), new MongoCode("function(o,p) { p.c += o.c; }"));
        return $count["retval"][0]['c'];
    }

    public function getFiles($uris)
    {
        $this->prepareConnections(true, false, true);

        $files = array();
        $cursor = $this->db_main->indir->find( array("_id" => array('$in' => $uris ) ) );
        $querys = array();
        foreach ($cursor as $ifile) {
            $s = (int)$ifile['s'];
            if (!array_key_exists($s, $querys)) $querys[$s] = array();

            if (array_key_exists('t', $ifile))
                $querys[$s][]=new MongoId($ifile['t']);
            else
                $querys[$s][]=new MongoId($ifile['_id']);
        }
        unset ($cursor);

        foreach ($querys as $s=>$suris) {
            $conn = $this->db_data[$s];
            $conn->connect();
            $cursor = $conn->foofind->foo->find(array("_id" => array('$in' => $suris ) ) );
            foreach ($cursor as $file) {
                $files[$file['_id']->__toString()] = $file;
            }
            unset ($cursor);
        }
        return $files;
    }

    public function getFile($hexuri)
    {
        $this->prepareConnections(true, false, true);

        $id = new MongoId($hexuri);
        $ifile = $this->db_main->indir->findOne( array("_id" =>$id) );
        if ($ifile==null) return null;
        if (array_key_exists('t', $ifile)) $id = $ifile['t'];
        $s = $ifile['s'];
        $conn = $this->db_data[$s];
        $conn->connect();
        
        return $conn->foofind->foo->findOne(array("_id" =>$id ) );
    }

    public function updateVotes($hexuri, $votes)
    {
        $this->prepareConnections(true, false, true);

        $id = new MongoId($hexuri);
        $ifile = $this->db_main->indir->findOne( array("_id" =>$id) );
        $s = $ifile['s'];

        $conn = $this->db_data[$s];
        $conn->connect();
        
        $conn->foofind->foo->update( array("_id" =>$id), array('$set' => array( 'vs' => $votes ) ) );
    }

    public function updateComments($hexuri, $comments)
    {
        $this->prepareConnections(true, false, true);

        $id = new MongoId($hexuri);
        $ifile = $this->db_main->indir->findOne( array("_id" =>$id) );
        $s = $ifile['s'];

        $conn = $this->db_data[$s];
        $conn->connect();
        
        $conn->foofind->foo->update( array("_id" =>$id), array('$set' => array( 'cs' => $comments ) ) );
    }

    public function getLastFilesIndexed( $limit )
    {
        $this->prepareConnections(true, false, true);

        $conn = $this->db_data[$this->servers[0]];
        $conn->connect();

        $cursor = $conn->foofind->foo->find(array('bl'=>0))->sort(array('$natural' => -1))->limit($limit);
        foreach ($cursor as $file) {
            $files []= $file;
        }
        unset ($cursor);
        return $files;
    }

    /*public function shakeFile($hexuri, $ip)
    {
        $shakekey = md5($hexuri.$ip);
        if (!$this->oCache->test($shakekey))
        {
            $this->ldb->shake->insert(array("_id"=>new MongoId($hexuri)));
            $this->oCache->save($shakekey);
        }
    }

    public function getShakenFiles()
    {
        $reduce = new MongoCode("function(o,p) { p.c += 1; }");
        $data = $this->ldb->shake->group(array("_id" => 1), array("c" => 0), $reduce);
        $this->oCache->save("shakes", $data);
        return $data;
    }

    public function getVotedFiles()
    {
        //$m = new MongoDate(time() - (30 * 24 * 60 * 60));
        $data = $this->db->votes->find(array("d" => array('$gt'=>$m)), array("_id" => 1, "k"=>1))->sort(array("d"=>-1));
        $this->oCache->save("voted", $data);
        return $data;
    }*/
}