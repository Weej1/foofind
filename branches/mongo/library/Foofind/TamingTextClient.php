<?php

function ensure_utf8($text)
{
    if(strpos(strtolower($text), "%u")!==FALSE)
        return utf8_urldecode($text);
    else if(!mb_check_encoding($text, "UTF-8"))
        return utf8_encode($text);
    return $text;
}

class TamingTextClient {
    var $conn;

    function  __construct($server, $port) {
        $this->server = $server;
        $this->port = $port;
    }

    /***
     * weigths: array of assoc array with keys
     *  n: ngrams weight
     *  l: levenshtein distance
     *  c: total count
     *  lang_code: language
     *  (type: type
     */
    function tameText($text, $weights, $limit, $maxdist, $minsimil, $dym=1, $rel=1)
    {
        $this->beginTameText($text, $weights, $limit, $maxdist, $minsimil, $dym, $rel);
        return $this->endTameText();
    }
    function beginTameText($text, $weights, $limit, $maxdist, $minsimil, $dym=1, $rel=1)
    {
        if (!$this->conn) $this->conn=fsockopen($this->server,$this->port);
        if (!$this->conn) return null;

        $params["t"] = ensure_utf8($text);
        $params["w"] = $weights;
        $params["l"] = $limit;
        $params["s"] = $minsimil;
        $params["md"] = $maxdist;
        $params["d"] = $dym;
        $params["r"] = $rel;
        $jparams = json_encode($params);
        $jparamslen = strlen($jparams);
        fwrite($this->conn, chr((int)($jparamslen/256)).chr($jparamslen%256).$jparams);
    }

    function endTameText()
    {
        $len = ord(fgetc($this->conn))<<8 | ord(fgetc($this->conn))+1;
        $line = fgets($this->conn, $len);
        return substr($line,0,-1);
    }

    function getFileInfo($file)
    {
        $this->beginGetFileInfo($file);
        return $this->endGetFileInfo();
    }

    function beginGetFileInfo($file)
    {
        if (!$this->conn) $this->conn=fsockopen($this->server,$this->port);
        if (!$this->conn) return null;
        $f = array("fn"=>array(), "md"=>array());

        foreach ($file["fn"] as $key=>$value)
        {
            $f['fn'][$key] = array("n"=>ensure_utf8($file["fn"][$key]["n"]), "x"=>ensure_utf8($file["fn"][$key]["x"]));
        }
        foreach ($file["md"] as $key=>$value)
        {
            $f['md'][ensure_utf8($key)] = ensure_utf8($file["md"][$key]);
        }
        $params["f"] = $f;
        $jparams = json_encode($params);
        $jparamslen = strlen($jparams);
        fwrite($this->conn, chr((int)($jparamslen/256)).chr($jparamslen%256).$jparams);
    }

    function endGetFileInfo()
    {
        $len = ord(fgetc($this->conn))<<8 | ord(fgetc($this->conn))+1;
        $line = fgets($this->conn, $len);
        return substr($line,0,-1);
    }
    
    function __destruct()
    {
        if ($this->conn) fclose($this->conn);
    }
}