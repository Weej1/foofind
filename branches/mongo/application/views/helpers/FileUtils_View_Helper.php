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
        $view->registerHelper($this, 'coalesce');
    }

    public function coalesce()
    {
        $args = func_get_args();
        foreach ($args as $arg) {
            if (isset($arg)) return $arg;
        }
        return "";
    }

    public function formatURL($url)
    {
        return rawurlencode($url);
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

            if ($w!='') $res = preg_replace("/\b(".preg_quote($w).")\b/iu", "<em>$1</em>", $res, -1,$found);
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
    if (isset($hours))
        return sprintf("%d:%02d:%02d", $hours, $mins, $secs);
    else
        return sprintf("%d:%02d", $mins, $secs);
    }

    function format($obj, $details=false)
    {
        $res = "";

        $md = $obj['file']['md'];
        
        if ($details) {
            $start = "<tr><td>";
            $middle = ":</td><td>";
            $end = "</td></tr>";
        } else {
            $start = "";
            $middle = ":&nbsp;";
            $end = ". ";
        }

        $type=null;
        if (isset($obj['view']['type'])) $type = $obj['view']['type'];
        switch ($type)
        {
            case "Audio":
               $res = $this->formatAudio($obj, $md, $details, $start, $middle, $end);
               break;
            case "Archive":
               $res = $this->formatArchive($obj, $md, $details, $start, $middle, $end);
               break;
            case "Document":
               $res = $this->formatDocument($obj, $md, $details, $start, $middle, $end);
               break;
            case "Image":
               $res = $this->formatImage($obj, $md, $details, $start, $middle, $end);
               break;
            case "Software":
               $res = $this->formatSoftware($obj, $md, $details, $start, $middle, $end);
               break;
            case "Video":
               $res = $this->formatVideo($obj, $md, $details, $start, $middle, $end);
               break;
            default:
               $res = "";
               break;
        }

        $element = $details?"table":"span";

        if ($res=='')
        {
            if (isset($obj['view']['nfn'])) $res = $start.$this->view->translate("Name").$middle.$this->searchable($details, $obj['view']['nfn']).$end;
            $desc = strtolower($type).":description";
            if (isset($md[$desc])) $res .= $start.$this->view->translate("Description").$middle.$this->searchable($details, $md[$desc]).$end;
        }
        if ($res!='') $res = "<$element>$res</$element>";

        $extra="";
        if ($details && $obj['view']['fnx']=="torrent")
        {
            if ($files = $this->getValue($md,"torrent:filepaths")) {
                $names = explode("//", $files);

                $sizes = $this->getValue($md,"torrent:filesizes");
                if ($sizes) $sizes = explode(" ", $sizes);

                for ($i=0; $i<count($names); $i++)
                {
                    if ($sizes[$i]) $size = " (".$this->formatSize($sizes[$i]).")"; else $size = "";
                    $extra .= "<li>{$names[$i]}$size</li>";
                }
                $extra = "<div class='download_file_torrent_files'>Torrent&nbsp;files: <ul>$extra</ul></div>";
            }
        }
        return $res.$extra;
    }

    function searchable($details, $text)
    {
        $text = strip_tags($text);
        if ($details)
            return "<a href='/{$this->view->lang}/search/?q=".urlencode($text)."'>".htmlentities($text, ENT_QUOTES, "UTF-8")."</a>";
        else
            return htmlentities($text, ENT_QUOTES, "UTF-8");
    }

    function formatAudio($obj, $md, $details, $start, $middle, $end)
    {
        $res = '';
        if ($artist = $this->getValue($md,"audio:artist")) $res .= $start.$this->view->translate("Artist").$middle.$this->searchable($details, $artist).$end;
        if ($title = $this->getValue($md,"audio:title")) $res .= $start.$this->view->translate("Title").$middle.$this->searchable($details, $title).$end;

        if ($album = $this->getValue($md,"audio:album")) {
            $res .= $start.$this->view->translate("Album").$middle.$this->searchable($details, $album);
            if (($year = $this->getValue($md,"audio:year")) && is_numeric($year) && $year>1901 && $year<2100)
                $res .= "&nbsp;($year)";
            $res .= $end;
        }
        if ($details && ($track = $this->getValue($md,"audio:track"))) $res .= $start.$this->view->translate("Track").$middle.$track.$end;
        if ($genre = $this->getValue($md,"audio:genre")) $res .= $start.$this->view->translate("Genre").$middle.$genre.$end;
        if ($len = $this->getValue($md,"audio:seconds")) $res .= $start.$this->view->translate("Length").$middle.$this->formatLength($len).$end;
        if ($bitrate = $this->getValue($md,"audio:bitrate")) {
            $bitrate = str_replace("~", "", $bitrate);
            $bitrate .= "&nbsp;kbit/s";
            if ($details &&  ($st = $this->getValue($md,"audio:soundtype"))) $bitrate .= " - ".$st;
            $res .= $start.$this->view->translate("Quality").$middle.$bitrate.$end;
        }
        return $res;
    }

    function formatDocument($obj, $md, $details, $start, $middle, $end)
    {
        $res = '';
        if ($title =  $this->getValue($md,"book:title","document:title")) $res .= $start.$this->view->translate("Title").$middle.$this->searchable($details, $title).$end;
        if ($author =  $this->getValue($md,"book:author", "document:author")) $res .= $start.$this->view->translate("Author").$middle.$this->searchable($details, $author).$end;
        if ($pages =  $this->getValue($md,"document:pages")) $res .= $start.$this->view->translate("Num. of pages").$middle.$pages.$end;

        if ($details)
        {
            if ($format = $this->getValue($md,"document:format")) {
                $res .= $start.$this->view->translate("Format").$middle.$format;
                if ($fversion = $this->getValue($md,"document:formatversion")) $res .= "&nbsp;v.$fversion";
                $res .= $end;
            }
            if ($version = $this->getValue($md,"document:version")) {
                $version = (int)$version;
                $res .= $start.$this->view->translate("Version").$middle.$version;
                if ($revision = $this->getValue($md,"document:revision")) $res .= "&nbsp;$revision";
                $res .= $end;
            }
        }

        return $res;
    }

    function formatImage($obj, $md, $details, $start, $middle, $end)
    {
        $res = '';
        if ($title = $this->getValue($md,"image:title")) $res .= $start.$this->view->translate("Title").$middle.$this->searchable($details, $title).$end;
        if ($artist = $this->getValue($md,"image:artist")) $res .= $start.$this->view->translate("Artist").$middle.$this->searchable($details, $artist).$end;

        if ($details)
        {
            if ($desc = $this->getValue($md,"image:description")) $res .= $start.$this->view->translate("Description").$middle.$desc.$end;
        }
        if (($width = $this->getValue($md,"image:width")) && ($height = $this->getValue($md,"image:height")))
            $res .= $start.$this->view->translate("Size").$middle.$width."x".$height.$end;
        if ($colors = $this->getValue($md,"image:colors"))
            $res .= $start.$this->view->translate("Colors").$middle.$colors.$end;

        return $res;
    }

    function formatVideo($obj, $md, $details, $start, $middle, $end)
    {
        $res = '';
        if ($title = $this->getValue($md,"video:title")) $res .= $start.$this->view->translate("Title").$middle.$this->searchable($details, $title).$end;
        if ($artist = $this->getValue($md,"video:artist")) $res .= $start.$this->view->translate("Artist").$middle.$this->searchable($details, $artist).$end;

        if ($len = $this->getValue($md,"video:minutes", "video:length", "video:duration"))
        {
            if (isset($md["video:minutes"])) $len *= 60;
            $res .= $start.$this->view->translate("Length").$middle.$this->formatLength($len).$end;
        }
        if (($width = $this->getValue($md,"video:width")) && ($height = $this->getValue($md,"video:height")))
            $res .= $start.$this->view->translate("Size").$middle.$width."x".$height.$end;

        if ($details) {
            if ($fps = $this->getValue($md,"video:framerate")) {
                $fps=(int)$fps;
                 $res .= $start.$this->view->translate("Quality").$middle.$fps." fps";
                 if ($codec = $this->getValue($md, "video:codec")) $res .= ' '.htmlentities($codec, ENT_QUOTES, "UTF-8");
                 $res .= $end;
            }
        }
        return $res;
    }

    function formatSoftware($obj, $md, $details, $start, $middle, $end)
    {
        $res = '';
        if ($title = $this->getValue($md,"application:title")) {
            $res .= $start.$this->view->translate("Title").$middle.$this->searchable($details, $title);
            if ($version = $this->getValue($md,"application:version")) $res .= "&nbsp;$version";
             $res .= $end;
        }
         
        if ($details) {
            if ($fversion = $this->getValue($md,"application:fileversion"))
                $res .= $start.$this->view->translate("Version").$middle.$fversion.$end;

            if ($os = $this->getValue($md,"application:os"))
                $res .= $start.$this->view->translate("OS").$middle.$os.$end;
        }
        return $res;
    }
     

    function formatArchive($obj, $md, $details, $start, $middle, $end)
    {
        $res = '';

        if ($title = $this->getValue($md,"archive:title","archive:name"))
            $res .= $start.$this->view->translate("Title").$middle.$this->searchable($details, $title).$end;

        if ($files = $this->getValue($md,"archive:files"))
            $res .= $start.$this->view->translate("Files").$middle.$files.$end;

        if ($details) {
            if ($folders = $this->getValue($md,"archive:folders"))
                $res .= $start.$this->view->translate("Folders").$middle.$folders.$end;
            if ($usize = $this->getValue($md,"archive:unpackedsize"))
                $res .= $start.$this->view->translate("Unpacked size").$middle.$this->formatSize($usize).$end;
        }
        return $res;
    }

    function getValue($md, $key1, $key2=null, $key3=null)
    {
        if (isset($md[$key1]))
            return $md[$key1];
        else if ($key2 && isset($md[$key2]))
            return $md[$key2];
        else if ($key3 && isset($md[$key3]))
            return $md[$key3];
        else
            return null;
    }
}
