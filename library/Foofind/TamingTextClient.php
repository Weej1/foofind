<?php
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
    function tameText($text, $weights, $limit, $strict)
    {
        if (!$this->conn) $this->conn=fsockopen($this->server,$this->port);
        if (!$this->conn) return null;
        $params["t"] = $text;
        $params["w"] = $weights;
        $params["l"] = $limit;
        $params["x"] = $strict;
        $jparams = json_encode($params);
        $jparamslen = strlen($jparams);
        
        fwrite($this->conn, chr((int)($jparamslen/256)).chr($jparamslen%256).$jparams);

        $len = ord(fgetc($this->conn))<<8 | ord(fgetc($this->conn));
        $line = fgets($this->conn, $len);
        return $line;
    }

    function __destruct()
    {
        if ($this->conn) fclose($this->conn);
    }
}