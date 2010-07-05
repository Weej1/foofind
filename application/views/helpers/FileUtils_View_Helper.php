<?php

class FileUtils_View_Helper extends Zend_View_Helper_Abstract
{
    public function registerHelper($view)
    {
        $view->registerHelper($this, 'formatURL');
        $view->registerHelper($this, 'formatHTML');
        $view->registerHelper($this, 'formatSize');
        $view->registerHelper($this, 'showMatches');
        $view->registerHelper($this, 'format');
    }
    public function formatURL($url)
    {
        return htmlentities($url, ENT_QUOTES, "UTF-8");
    }

    public function formatHTML($html)
    {
        return htmlentities($html, ENT_QUOTES, "UTF-8");
    }

    public function showMatches($text, $query, $url, &$found = null)
    {
        $res = $text;

        preg_match_all('/"(?:\\\\.|[^\\\\"])+"|\S+/', $query, $words);
        foreach ($words[0] as $w)
        {
            if ($w[0]=='"') $w = substr($w, 1, -1);
            if ($url)
                $w = urlencode($w);
            else
                $w = htmlentities($w, ENT_QUOTES, "UTF-8");

            if ($w!='') $res = preg_replace("/\b(".preg_quote($w).")\b/iu", "<b>$1</b>", $res, -1,$found);
        }
        $found = $found>0;
        return $res;
    }

    function formatSize($bytes)
    {
        $size = $bytes / 1024;
        if ($size < 1024)
        {
            $size = number_format($size, 2);
            $size .= '&nbsp;KB';
        }
        else
        {
            if ($size / 1024 < 1024)
            {
                $size = number_format($size / 1024, 2);
                $size .= '&nbsp;MB';
            }
            else if ($size / 1024 / 1024 < 1024)
            {
                $size = number_format($size / 1024 / 1024, 2);
                $size .= '&nbsp;GB';
            }
        }
        return $size;
    }
    
    function formatLength($text)
    {
    $secs = (int)$text;
    $mins = 0;
    if ($secs>59) {
        $mins = $secs/60;
        $secs = $secs%60;
        if ($mins>59) {
            $hours = $mins/60;
            $mins = $mins%60;
        }
    }
    if ($hours)
        return sprintf("%d:%02d:%02d", $hours, $mins, $secs);
    else
        return sprintf("%d:%02d", $mins, $secs);
    }

    function format($obj)
    {
        switch ($obj['view']['type'])
        {
            case "Audio":
               return $this->formatAudio($obj);
            case "Archive":
               return $this->formatArchive($obj);
            case "Document":
               return $this->formatDocument($obj);
            case "Image":
               return $this->formatImage($obj);
            case "Software":
               return $this->formatSoftware($obj);
            case "Video":
               return $this->formatVideo($obj);
            default:
                return "";
        }
    }

    function formatAudio($obj)
    {
        $md = $obj['file']['md'];
        $res = '';
        if ($artist = $md["audio:artist"]) $res .= $this->view->translate("Artist").":&nbsp;$artist. ";
        if ($title = $md["audio:title"]) $res .= $this->view->translate("Title").":&nbsp;$title. ";
        if ($album = $md["audio:album"]) {
            $res .= $this->view->translate("Album").":&nbsp;$album";
            if (($year = $md["audio:year"]) && is_numeric($year) && $year>1901 && $year<2100)
                $res .= "&nbsp;($year). ";
            else
                $res .= ". ";
        }
        if ($genre = $md["audio:genre"]) $res .= $this->view->translate("Genre").":&nbsp;$genre. ";
        if ($len = $md["audio:seconds"]) $res .= $this->view->translate("Length").":&nbsp;".$this->formatLength($len).". ";
        if ($bitrate = $md["audio:bitrate"]) $res .= $this->view->translate("Bitrate").":&nbsp;$bitrate&nbsp;kbit/s. ";
        return $res;
    }

    function formatDocument($obj)
    {
        $md = $obj['file']['md'];
        $res = '';
        if ($show_title && $title = $md["document:title"]) $res .= "$title<br/>";
        if ($pages = $md["document:pages"]) $res .= $this->view->translate("Nº of pages").":&nbsp;$pages. ";
        return $res;
    }

    function formatImage($obj)
    {
        $md = $obj['file']['md'];
        $res = '';
        if ($show_title && $title = $md["image:title"]) $res .= "$title<br/>";
        if (($width = $md["image:width"]) && ($height = $md["image:height"]))
            $res .= $this->view->translate("Size").":&nbsp;${width}x$height. ";
        if ($colors = $md["image:colors"])
            $res .= $this->view->translate("Colors").":&nbsp;$colors. ";
        return $res;
    }

    function formatVideo($obj)
    {
        $md = $obj['file']['md'];
        $res = '';
        if ($show_title && $title = $md["video:title"]) $res .= "$title<br/>";
        if (($len = $md["video:minutes"]*60) || ($len = $md["video:length"]))
            $res .= $this->view->translate("Length").":&nbsp;".$this->formatLength($len).". ";
        if (($width = $md["video:width"]) && ($height = $md["video:height"]))
            $res .= $this->view->translate("Size").":&nbsp;${width}x$height. ";
        return $res;
    }

    function formatSoftware($obj)
    {
        $md = $obj['file']['md'];
        $res = '';
        if ($show_title && $title = $md["application:title"]) $res .= "$title<br/>";
        return $res;
    }

    function formatArchive($obj)
    {
        $md = $obj['file']['md'];
        $res = '';
        if ($files = $md["archive:files"])
            $res .= $this->view->translate("Files").":&nbsp;$files. ";
        return $res;
    }
}
