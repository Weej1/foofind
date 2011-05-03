:<?php

require_once APPLICATION_PATH . '/models/Files.php';
require_once APPLICATION_PATH.'../../library/Sphinx/sphinxapi.php';
require_once APPLICATION_PATH.'../../library/Foofind/TamingTextClient.php';

class Zend_Controller_Action_Helper_Fileutils extends Zend_Controller_Action_Helper_Abstract {

    function parse_uint($string) {
        $x = (float)$string;
        if ($x > (float)2147483647)
            $x -= (float)"4294967296";
        return (int)$x;
    }

    function longs2uri($l1, $l2, $l3)
    {
        if (PHP_INT_SIZE>4)
            return pack("III", (int)$l1, (int)$l2, (int)$l3);
        else
            return pack("III", $this->parse_uint($l1), $this->parse_uint($l2), $this->parse_uint($l3));
    }

    function uri2url($uri) {
       $url = base64_encode($uri);
       $url = str_replace('/', '-', $url);
       $url = str_replace('+', '!', $url);
       return $url;
    }

    function url2uri($url) {
       $uri = str_replace('-', '/', $url);
       $uri = str_replace('!', '+', $uri);
       $uri = base64_decode($uri);
       return $uri;
    }

    function uri2hex($uri) {
        return bin2hex($uri);
    }

    function hex2uri($hexuri) {
        return pack("H*" , $hexuri);
    }

    function chooseFilename(&$obj, $text = null)
    {
        $srcs = $obj['file']['src'];
        $fns = $obj['file']['fn'];
        $hist= "";
        $maxCount = 0; $hasText = 0;
        foreach ($srcs as $hexuri => $src)
        {
            
            if (isset($src['bl']) && $src['bl']!=0) continue;
            $srcfns = $src['fn'];
            foreach ($srcfns as $crc => $srcfn)
            {
                $fnn = $fns[$crc]['n'];
                if (strlen(trim($fnn))==0) continue;

                $thisHasText = 0;
                if (isset($fns[$crc]['c']))
                    $fns[$crc]['c'] += $srcfn['m'];
                else
                    $fns[$crc]['c'] = $srcfn['m'];

                if ($text!=null) {
                    if (stripos($fnn, $text)!==false)
                        $thisHasText = 2000;
                    else {
                        $matches = 0;
                        $words = explode(" ", $text);
                        foreach ($words as $word) {
                            $word = preg_quote($word, "/");
                            $matches += preg_match("/(\b$word\b)/i", $fnn);
                        }

                        if ($matches>0) $thisHasText = 1000 + $matches;
                    }
                }
                $obj['file']['fn'][$crc]['tht'] = $thisHasText;

                $better = $fns[$crc]['c']>$maxCount;

                if (($thisHasText > $hasText) || ($better && ($thisHasText==$hasText)))
                {
                    $hasText = $thisHasText;
                    $chosen = $crc;
                    $maxCount = $fns[$crc]['c'];
                }
            }
        }

        $obj['view']['url'] = $this->uri2url($this->hex2uri($obj['file']['_id']->__toString()));

        if (isset($chosen))
        {
            $filename = $fns[$chosen]['n'];
            $ext = $fns[$chosen]['x'];
        } else {
            // uses filename from src
            $srcurl = null;
            foreach ($srcfns as $crc => $srcfn)
                if (strpos($src['url'], "/")!=-1) $srcurl = $src['url'];

            if ($srcurl==null) return;
            $srcurl = substr($srcurl, strrpos($srcurl, "/")+1);
            $filename = substr($srcurl, 0, strrpos($srcurl, "."));
            $ext = substr($srcurl, strrpos($srcurl, ".")+1);
        }
        $obj['view']['fn'] = $filename;
        $obj['view']['efn'] = str_replace(" ", "%20", $filename);
        $obj['view']['fnx'] = $ext;
            
        // clean filename
        $end = strripos($filename, ".$ext");
        if ($end === false)
            $nfilename = $filename;
        else
            $nfilename = trim(substr($filename, 0, $end));

        if (!strchr($nfilename, " ")) $nfilename = str_replace(array("_", "."), " ", $nfilename);
        $obj['view']['nfn'] = $nfilename;
    }

    function getDomain($url)
    {
        $urls = explode( '/', $url );
        $url = $urls[2];

        $urls = explode( '.', $url );
        $i = count($urls) - 1;

        if(strlen($urls[$i]) <= 2 && strlen($urls[$i-1]) <= 2 ) {
            $ret = $urls[$i-2].'.'.$urls[$i-1].'.'.$urls[$i];
        } else {
            $ret = $urls[$i-1].'.'.$urls[$i];
        }
        return $ret;
    }

    function buildSourceLinks(&$obj, $prevsrc=NULL)
    {
        if (!isset($obj['view']['fn'])) $this->chooseFilename($obj);

        $obj['view']['action'] = 'Download';
        $srcs = $obj['file']['src'];
        $maxWeight = 0;
        foreach ($srcs as $hexuri => $src)
        {
            if (isset($src['bl']) && $src['bl']!=0) continue;
            $continue = false;
            $join = false;
            $type = (int)$src['t'];
            $linkWeight = 0;
            switch ($type)
            {
                case Model_Files::SOURCE_GNUTELLA:
                    $linkWeight = 0.2;
                    $tip = "Gnutella";
                    $source = $icon = "gnutella";
                    $part = "xt=urn:sha1:".$src['url'];
                    $join = true;
                    $count = (int)$src['m'];
                    break;
                case Model_Files::SOURCE_ED2K:
                    $linkWeight = 0.1;
                    $tip = "ED2K";
                    $icon = $source = "ed2k";
                    $url = "ed2k://|file|".$obj['view']['efn']."|".$obj['file']['z']."|".$src['url']."|/";
                    $count = (int)$src['m'];
                    break;
                case Model_Files::SOURCE_BITTORRENT:
                    $linkWeight = 0.8;
                    $icon = "torrent";
                    $url = $src['url'];
                    $tip = $source = $this->getDomain($url);
                    break;
                case Model_Files::SOURCE_TIGER:
                    $tip = "Gnutella";
                    $source = $icon = "gnutella";
                    $part = "xt=urn:tiger:".$src['url'];
                    break;
                case Model_Files::SOURCE_MD5:
                    $tip = "Gnutella";
                    $source = $icon = "gnutella";
                    $part = "xt=urn:md5:".$src['url'];
                    break;
                 case Model_Files::SOURCE_BTH:
                    $linkWeight = 0.1;
                    $tip = "Torrent MagnetLink";
                    $source = $icon = "tmagnet";
                    $join = true;
                    $count = (int)$src['m'];

                    $trackers="";
                    if (isset($obj['file']['md']['torrent:tracker']))
                    {
                        $trackers = '&tr='.urlencode($obj['file']['md']['torrent:trackers']);
                        $linkWeight = 0.7;
                    }

                    if (isset($obj['file']['md']['torrent:trackers']))
                    {
                        $linkWeight = 0.7;
                        foreach (explode(' ', $obj['file']['md']['torrent:trackers']) as $tr)
                            $trackers .= '&tr='.urlencode($tr);
                    }

                    $part = "xt=urn:btih:".$src['url'].$trackers;
                    break;
                case Model_Files::SOURCE_JAMENDO:
                case Model_Files::SOURCE_HTTP:
                case Model_Files::SOURCE_MEGAUPLOAD:
                case Model_Files::SOURCE_RAPIDSHARE:
                case Model_Files::SOURCE_MEGAVIDEO:
                case Model_Files::SOURCE_4SHARED:
                case Model_Files::SOURCE_BLIPTVD:
                case Model_Files::SOURCE_BLIPTVS:
                case Model_Files::SOURCE_VIMEO:
                case Model_Files::SOURCE_VEOH:
                case Model_Files::SOURCE_YOUTUBE:
                    $linkWeight = 1;
                    // prefer megavideo for streaming searches
                    if ((!isset($prevsrc) || strlen($prevsrc)==0 || strpos($prevsrc,"s")!==FALSE) && in_array($type, Model_Files::src2ints("s"))) {
                        $obj['view']['action'] = 'Watch';
                        $linkWeight *= 2;
                    }
                    
                    $icon = "web";
                    $url = $src['url'];
                    $tip = $source = $this->getDomain($url);
                    // Temporary solves megaupload-megavideo conflict
                    if (($type==Model_Files::SOURCE_MEGAUPLOAD) && (stripos($url, "http://www.megavideo.com/") === 0))
                        $continue = true;

                    if ($type==Model_Files::SOURCE_MEGAVIDEO) {
                        $found=array();
                        preg_match("\"http:\/\/www.megavideo.com\/\?(\w)=([^&]*).*\"", $url, $found);
                        if ($found[1]) $obj['view']['sources'][$source]['idtype'] = $found[1];
                        if ($found[1]) $obj['view']['sources'][$source]['id'] = $found[2];
                    }
                    break;
                case Model_Files::SOURCE_FTP:
                    $linkWeight = 0.9;
                    $icon = "ftp";
                    $url = $src['url'];
                    $tip = $source = $this->getDomain($url);
                    break;
                default:
                    $continue = true;
                    break;
            }

            if ($continue) continue;

            if ($join) $obj['view']['sources'][$source]['join'] = true;
            $obj['view']['sources'][$source]['tip'] = $tip;
            $obj['view']['sources'][$source]['icon'] = $icon;
            
            if (isset($count)) {
                $obj['view']['sources'][$source]['count'] = $count;
                unset($count);
            }

            if (isset($part))
            {
                $obj['view']['sources'][$source]['parts'] []= $part;
                unset($part);
            }
            
            if (isset($url))
            {
                $obj['view']['sources'][$source]['urls'] []= $url;
                unset($url);
            }

            if ($linkWeight>$maxWeight) {
                $maxWeight = $linkWeight;
                $obj['view']['source'] = $source;
            }
        }

        foreach ($obj['view']['sources'] as $src=>$info)
        {
            if (isset($info['join']))
            {
                if (isset($obj['file']['z'])) $size = "&xl=".$obj['file']['z']; else $size="";
                $url = "magnet:?dn=".$obj['view']['efn'].$size."&".implode("&", $info['parts']);
                $obj['view']['sources'][$src]['urls'] []= $url;
            } else {
                if (!isset($info['urls'])) unset($obj['view']['sources'][$src]);
            }
        }

    }

    function chooseType(&$obj, $type=null)
    {
        if ($type==null) {
            if (isset($obj["file"]["ct"])) $type = $obj["file"]["ct"];
            if ($type==null && isset($obj["search"]["ct"])) $type = $obj["search"]["ct"];
            if ($type==null && isset($obj["view"]["fnx"]) ) $type = Model_Files::ext2ct($obj['view']['fnx']);
            if ($type!=null) $type = Model_Files::ct2string($type);
        }

        if ($type!=null) {
            $obj['view']['type'] = $type;
        }
    }

    function searchRelatedFiles(&$obj)
    {

        $phrases = $obj["finfo"]["ph"];
        $query = "";
        if ($phrases==null)
            $parts = array($obj['view']['nfn']);
        elseif (count($phrases)==1) {
            $parts = $phrases[0];
        } else {
            $parts = $phrases;
        }
        foreach ($parts as $part)
        {
            if (is_array($part)) $part = implode(" ", $part);
            $minm = (int)(substr_count($part, " ")*0.5);
            $part = "\"$part\"";
            if ($minm>1) $part = "($part/$minm)";
            $query .= "|$part";
        }

        $query = substr($query, 1);
        $mode = SPH_MATCH_EXTENDED2;
        
        $config = Zend_Registry::get('config');
        $sphinxServer = $config->sphinx->server;
        $cl = new SphinxClient();
        $cl->SetServer( $sphinxServer, 3312 );
        $cl->SetMatchMode( $mode );
        $cl->SetRankingMode( SPH_RANK_MATCHANY );

        // search field weights
        $weights = array();

        // filenames
        $weights["fn1"] = 10;
        for ($i = 2; $i < 21; $i++)
            $weights["fn$i"] = 1;

        $cl->SetFieldWeights($weights);
        $cl->SetSelect("*, @weight as sw,  w*@weight as fw");
        $cl->SetSortMode( SPH_SORT_EXTENDED, "fw DESC" );
        $cl->SetMaxQueryTime(1000);
        $cl->SetLimits( 0, 6, 6, 10000);
        $result = $cl->Query($query, 'idx_files');

        if ( $result !== false && !empty($result["matches"]) ) {
            $ids = array();
            foreach ( $result["matches"] as $doc => $docinfo )
            {
                $uri = $this->longs2uri($docinfo["attrs"]["uri1"], $docinfo["attrs"]["uri2"], $docinfo["attrs"]["uri3"]);
                $hexuri = $this->uri2hex($uri);
                
                // ignore showing file
                if ($hexuri == $obj['file']['_id']->__toString()) continue;
                
                $docs[$hexuri] = array();
                $docs[$hexuri]["search"] = $docinfo['attrs'];
                $docs[$hexuri]["search"]["id"] = $doc;
                $ids []= new MongoId($hexuri);
            }

            $fmodel = new Model_Files();
            $files = $fmodel->getFiles( $ids );
            foreach ($files as $file) {
                $hexuri = $file['_id']->__toString();
                $rel = $docs[$hexuri];
                $rel['file'] = $file;
                $this->chooseFilename($rel, $query);
                $obj['view']['related'] []= $rel;
            }
        }
    }
}