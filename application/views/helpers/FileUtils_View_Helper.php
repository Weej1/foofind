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

        switch ($obj['view']['type'])
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
               return "";
        }
        if ($details && $res!='') $res = "<table>$res</table>";

        if ($details && $obj['view']['fnx']=="torrent")
        {
            if ($files = $md["torrent:filepaths"]) {
                $l=2;
                while ($more = $md["torrent:filepaths$l"]) { $files .= " ".$more; $l++;}

                $names = explode("//", $files);

                $sizes = $md["torrent:filesizes"];
                if ($sizes)
                {
                    $l=2;
                    while ($more = $md["torrent:filesizes$l"]) { $sizes .= " ".$more; $l++;}
                    $sizes = explode(" ", $sizes);
                }

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
        if ($details)
            return "<a href='/{$this->view->lang}/search/?q=".urlencode($text)."'>".htmlentities($text, ENT_QUOTES, "UTF-8")."</a>";
        else
            return htmlentities($text, ENT_QUOTES, "UTF-8");
    }

    function formatAudio($obj, $md, $details, $start, $middle, $end)
    {
        $res = '';
        if ($artist = $md["audio:artist"]) $res .= $start.$this->view->translate("Artist").$middle.$this->searchable($details, $artist).$end;
        if ($title = $md["audio:title"]) $res .= $start.$this->view->translate("Title").$middle.$this->searchable($details, $title).$end;

        if ($album = $md["audio:album"]) {
            $res .= $start.$this->view->translate("Album").$middle.$this->searchable($details, $album);
            if (($year = $md["audio:year"]) && is_numeric($year) && $year>1901 && $year<2100)
                $res .= "&nbsp;($year)";
            $res .= $end;
        }
        if ($details && $track = $md["audio:track"]) $res .= $start.$this->view->translate("Track").$middle.$track.$end;
        if ($genre = $md["audio:genre"]) $res .= $start.$this->view->translate("Genre").$middle.$genre.$end;
        if ($len = $md["audio:seconds"]) $res .= $start.$this->view->translate("Length").$middle.$this->formatLength($len).$end;
        if ($bitrate = $md["audio:bitrate"]) {
            $bitrate = str_replace("~", "", $bitrate);
            $bitrate .= "&nbsp;kbit/s";
            if ($details && $md["audio:soundtype"]) $bitrate .= " - ".$md["audio:soundtype"];
            $res .= $start.$this->view->translate("Quality").$middle.$bitrate.$end;
        }
        return $res;
    }

    function formatDocument($obj, $md, $details, $start, $middle, $end)
    {
        $res = '';
        if (($title = $md["book:title"]) || ($title = $md["document:title"])) $res .= $start.$this->view->translate("Title").$middle.$this->searchable($details, $title).$end;
        if (($author = $md["book:author"]) || ($author = $md["document:author"])) $res .= $start.$this->view->translate("Author").$middle.$this->searchable($details, $author).$end;
        if ($pages = $md["document:pages"]) $res .= $start.$this->view->translate("Nº of pages").$middle.$pages.$end;

        if ($details)
        {
            if ($format = $md["document:format"]) {
                $res .= $start.$this->view->translate("Format").$middle.$format;
                if ($fversion = $md["document:formatversion"]) $res .= "&nbsp;v.$fversion";
                $res .= $end;
            }
            if ($version = (int)$md["document:version"]) {
                $res .= $start.$this->view->translate("Version").$middle.$version;
                if ($revision = $md["document:revision"]) $res .= "&nbsp;$revision";
                $res .= $end;
            }
        }

        return $res;
    }

    function formatImage($obj, $md, $details, $start, $middle, $end)
    {
        $res = '';
        if ($title = $md["image:title"]) $res .= $start.$this->view->translate("Title").$middle.$this->searchable($details, $title).$end;
        if ($artist = $md["image:artist"]) $res .= $start.$this->view->translate("Artist").$middle.$this->searchable($details, $artist).$end;

        if ($details)
        {
            if ($desc = $md["image:description"]) $res .= $start.$this->view->translate("Description").$middle.$desc.$end;
        }
        if (($width = $md["image:width"]) && ($height = $md["image:height"]))
            $res .= $start.$this->view->translate("Size").$middle.$width."x".$height.$end;
        if ($colors = $md["image:colors"])
            $res .= $start.$this->view->translate("Colors").$middle.$colors.$end;

        return $res;
    }

    function formatVideo($obj, $md, $details, $start, $middle, $end)
    {
        $res = '';
        if ($title = $md["video:title"]) $res .= $start.$this->view->translate("Title").$middle.$this->searchable($details, $title).$end;
        if ($artist = $md["video:artist"]) $res .= $start.$this->view->translate("Artist").$middle.$this->searchable($details, $artist).$end;

        if (($len = $md["video:minutes"]*60) || ($len = $md["video:length"]))
            $res .= $start.$this->view->translate("Length").$middle.$this->formatLength($len).$end;
        if (($width = $md["video:width"]) && ($height = $md["video:height"]))
            $res .= $start.$this->view->translate("Size").$middle.$width."x".$height.$end;

        if ($details) {
            if ($fps = (int)$md["video:framerate"]) {
                 $res .= $start.$this->view->translate("Quality").$middle.$fps." fps";
                 if ($codec = $md["video:codec"]) $res .= ' '.htmlentities($codec, ENT_QUOTES, "UTF-8");
                 $res .= $end;
            }
        }
        return $res;
    }

    function formatSoftware($obj, $md, $details, $start, $middle, $end)
    {
        $res = '';
        if ($title = $md["application:title"]) {
            $res .= $start.$this->view->translate("Title").$middle.$this->searchable($details, $title);
            if ($version = $md["application:version"]) $res .= "&nbsp;$version";
             $res .= $end;
        }
         
        if ($details) {
            if ($fversion = $md["application:fileversion"])
                $res .= $start.$this->view->translate("Version").$middle.$fversion.$end;

            if ($os = $md["application:os"])
                $res .= $start.$this->view->translate("OS").$middle.$os.$end;
        }
        return $res;
    }
     

    function formatArchive($obj, $md, $details, $start, $middle, $end)
    {
        $res = '';

        if (($title = $md["archive:title"]) || ($title = $md["archive:name"]))
            $res .= $start.$this->view->translate("Title").$middle.$this->searchable($details, $title).$end;

        if ($files = $md["archive:files"])
            $res .= $start.$this->view->translate("Files").$middle.$files.$end;

        if ($details) {
            if ($folders = $md["archive:folders"])
                $res .= $start.$this->view->translate("Folders").$middle.$folders.$end;
            if ($usize = $md["archive:unpackedsize"])
                $res .= $start.$this->view->translate("Unpacked size").$middle.$this->formatSize($usize).$end;
        }
        return $res;
    }
}
