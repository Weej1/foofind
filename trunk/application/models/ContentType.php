<?php

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

function format($md)
{
    return "";
}

function formatAudio($md)
{
    $res = '';
    if ($artist = $md["audio:artist"]) $res .= "Artist:&nbsp;$artist. ";
    if ($title = $md["audio:title"]) $res .= "Title:&nbsp;$title. ";
    if ($album = $md["audio:album"]) {
        $res .= "Album:&nbsp;$album";
        if (($year = $md["audio:year"]) && is_numeric($year) && $year>1901 && $year<2100)
            $res .= "&nbsp;($year). ";
        else
            $res .= ". ";
    }
    if ($genre = $md["audio:genre"]) $res .= "Genre:&nbsp;$genre. ";
    if ($len = $md["audio:seconds"]) $res .= "Length:&nbsp;".formatLength($len).". ";
    if ($bitrate = $md["audio:bitrate"]) $res .= "Bitrate:&nbsp;$bitrate&nbsp;kbit/s. ";
    return $res;
}

function formatDocument($md)
{
    return "";
}

function formatImage($md)
{
    return "";
}

function formatVideo($md)
{
    return "";
}

function formatSoftware($md)
{
    return "";
}

function formatArchive($md)
{
    return "";
}

global $content;
$content = array(
    'assoc' => array(1 => 'Audio', 2 => 'Video', 3 => 'Document', 4 => 'Archive', 5 => 'Image', 6 => 'Software',
                     7 => 'Archive', 8 => 'Software', 9 => 'Document', 10 => 'Document', 11 => 'Document'),

    'types' => array(
        'Audio' => array( 'ext' => array("aiff", "aif", "aifc", "au", "snd", "raw", "wav", "flac", "la", "pac", "m4a", "ape", "rka", "shn", "wv", "wma", "mp2", "mp3", "ogg", "m4a", "mp4", "m4p", "aac", "mpc", "mp+", "mpp", "ra", "rma", "swf"),
                          'metadata' => array("audio:artist", "audio:title", "audio:album", "audio:year", "audio:genre", "audio:seconds", "audio:bitrate")
            ),
        'Video' => array('ext' => array("3gp", "3g2", "gif", "asf", "avi", "dat", "flw", "swf", "mkv", "wrap", "mng", "mov", "mpeg", "mpg", "mpe", "nsv", "ogm", "ogv", "svi", "rm", "wmv", "divx", "xvid"),
                          'metadata' => array()
            ),
        'Image' => array('ext' => array("act", "art", "bmp", "blp", "cit", "cpt", "cut", "dib", "djvu", "egt", "exif", "gif", "icns", "ico", "iff", "ilbm", "ibm", "jng", "jpeg", "jpg", "jp2", "j2k", "ppm", "pgm", "pbm", "pnm", "pcf", "pcx", "pdn", "pgm", "pct", "png", "pnm", "ppm", "psb", "psd", "pdd", "psp", "px", "pxr", "qfx", "raw", "raf", "crw", "cr2", "tif", "kdc", "dcr", "mrw", "nef", "orf", "dng", "ptx", "pef", "arw", "srf", "sr2", "x3f", "erf", "mef", "mos", "raw", "tif", "r3d", "fff", "sct", "sgi", "rgb", "int", "bw", "tga", "targa", "icb", "vda", "vst", "pix", "tif", "tiff", "xbm", "xcf", "xpm", "awg", "ai", "eps", "cgm", "cdr", "cmx", "dxf", "egt", "svg", "wmf", "emf", "art", "xar"),
                          'metadata' => array()
            ),
        'Document' => array('ext' => array("abw", "afp", "ans", "asc", "aww", "csv", "cwk", "doc", "docx", "dot", "dotx", "egt", "ftm", "ftx", "html", "hwp", "lwp", "mcw", "nb", "nbp", "odt", "ott", "pages", "pap", "pdf", "rtf", "rtf", "sdw", "stw", "sxw", "tex", "info", "txt", "uoml", "wpd", "wps", "wpt", "wrf", "wri", "xhtml", "xls", "xml", "odp", "otp", "pps", "ppt", "sti", "sxi", "ods", "ots"),
                          'metadata' => array()
            ),
        'Software' => array('ext' => array("class", "com", "exe", "jar", "dll", "ocx"),
                          'metadata' => array()
            ),
        'Archive' => array('ext' => array("7z", "ace", "alz", "at3", "bke", "arc", "dds", "arj", "big", "bkf", "bzip2", "cab", "cpt", "sea", "daa", "deb", "dmg", "eea", "egt", "ecab", "ess", "gho", "gzip", "jar", "lbr", "lqr", "lha", "lzo", "lzx", "bin", "pak", "par", "par2", "pk4", "rar", "sit", "sitx", "tar", "gz", "tb", "tib", "uha", "vsa", "z", "zoo", "zip", "torrent"),
                          'metadata' => array()
            )
        ),

    'sources' => array(
        'limewire' => array('types' => array(1)),
        'emule' => array('types' => array(2)),
        'torrent' => array('types' => array(3,7)),
        'dd' => array('types' => array(4,8,9)),
        'jamendo' => array('types' => array(4)),
        'http' => array('types' => array(8)),
        'ftp' => array('types' => array(9))
    )
);

$content['crcMD'] = array();
foreach ($content['types'] as $type => $info)
{
    $content['types'][$type]['crcExt'] = array();
    foreach ($info['ext'] as $ext)
    {
        $crc = crc32($ext);
        if ($crc<0) $crc+=4294967296;
        $content['types'][$type]['crcExt'] []= $crc;
        $content['extAssoc'][$ext] = $type;
    }

    foreach ($info['metadata'] as $md)
    {
        $crc = crc32($md);
        if ($crc<0) $crc+=4294967296;
        $content['crcMD'] []= $crc;
    }
}