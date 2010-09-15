<?php

require_once ( APPLICATION_PATH . '/models/Files.php' );

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
            $srcfns = $src['fn'];
            foreach ($srcfns as $crc => $srcfn)
            {
                $thisHasText = 0;
                if (isset($fns[$crc]['c']))
                    $fns[$crc]['c'] += $srcfn['m'];
                else
                    $fns[$crc]['c'] = $srcfn['m'];

                if ($text!=null) {
                    if (stripos($fns[$crc]['n'], $text)!==false)
                        $thisHasText = 2000;
                    else {
                        $matches = 0;
                        $words = explode(" ", $text);
                        foreach ($words as $word) {
                            $word = preg_quote($word, "/");
                            $matches += preg_match("/(\b$word\b)/i", $fns[$crc]['n']);
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
        if (isset($chosen)){
            $obj['view']['fn'] = $fns[$chosen]['n'];
            $obj['view']['efn'] = str_replace(" ", "%20", $fns[$chosen]['n']);
            $obj['view']['fnx'] = $fns[$chosen]['x'];
        }
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

    function buildSourceLinks(&$obj)
    {
        if (!isset($obj['view']['fn'])) $this->chooseFilename($obj);

        $srcs = $obj['file']['src'];
        $maxWeight = 0;
        foreach ($srcs as $hexuri => $src)
        {
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
                    $url = "ed2k://|file|".$obj['view']['efn']."|".$obj['file']['s']."|".$src['url']."|/";
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

                    $linkWeight = 1;

                    // prefer megavideo for streaming searches
                    if (($_COOKIE['src']=='s') && ($type==Model_Files::SOURCE_MEGAVIDEO)) $linkWeight *= 2;

                    $icon = "web";
                    $url = $src['url'];
                    $tip = $source = $this->getDomain($url);
                    // Temporary solves megaupload-megavideo conflict
                    if (($type==Model_Files::SOURCE_MEGAUPLOAD) && (stripos($url, "http://www.megavideo.com/") === 0))
                        $continue = true;
                    
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
            if ($info['join'])
            {
                if (isset($obj['file']['s'])) $size = "&xl=".$obj['file']['s']; else $size="";
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
            try { $type = $obj["file"]["ct"]; } catch (Exception $ex) { }
            if ($type==null) try { $type = $obj["search"]["ct"]; } catch (Exception $ex) { }
            
            if ($type!=null) try { $type = Model_Files::ct2string($type); } catch (Exception $ex) { }
        }

        if ($type!=null) {
            $obj['view']['type'] = $type;
        }
    }
}