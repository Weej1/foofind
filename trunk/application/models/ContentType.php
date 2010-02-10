<?php
global $content;
$content = array(
    'types' => array(
        'Audio' => array( 'ext' => array("aiff", "aif", "aifc", "au", "snd", "raw", "wav", "flac", "la", "pac", "m4a", "ape", "rka", "shn", "wv", "wma", "mp2", "mp3", "ogg", "m4a", "mp4", "m4p", "aac", "mpc", "mp+", "mpp", "ra", "rma", "swf")
                    ),
        'Document' => array( 'ext' => array("abw", "afp", "ans", "asc", "aww", "csv", "cwk", "doc", "docx", "dot", "dotx", "egt", "ftm", "ftx", "html", "hwp", "lwp", "mcw", "nb", "nbp", "odt", "ott", "pages", "pap", "pdf", "rtf", "rtf", "sdw", "stw", "sxw", "tex", "info", "txt", "uoml", "wpd", "wps", "wpt", "wrf", "wri", "xhtml", "xls", "xml", "odp", "otp", "pps", "ppt", "sti", "sxi", "ods", "ots")
                    ),
        'Image' => array('ext' => array("act", "art", "bmp", "blp", "cit", "cpt", "cut", "dib", "djvu", "egt", "exif", "gif", "icns", "ico", "iff", "ilbm", "ibm", "jng", "jpeg", "jpg", "jp2", "j2k", "ppm", "pgm", "pbm", "pnm", "pcf", "pcx", "pdn", "pgm", "pct", "png", "pnm", "ppm", "psb", "psd", "pdd", "psp", "px", "pxr", "qfx", "raw", "raf", "crw", "cr2", "tif", "kdc", "dcr", "mrw", "nef", "orf", "dng", "ptx", "pef", "arw", "srf", "sr2", "x3f", "erf", "mef", "mos", "raw", "tif", "r3d", "fff", "sct", "sgi", "rgb", "int", "bw", "tga", "targa", "icb", "vda", "vst", "pix", "tif", "tiff", "xbm", "xcf", "xpm", "awg", "ai", "eps", "cgm", "cdr", "cmx", "dxf", "egt", "svg", "wmf", "emf", "art", "xar")
                    ),
        'Video' => array('ext' => array("3gp", "3g2", "gif", "asf", "avi", "dat", "flw", "swf", "mkv", "wrap", "mng", "mov", "mpeg", "mpg", "mpe", "nsv", "ogm", "ogv", "svi", "rm", "wmv", "divx", "xvid")
                    ),
        'File' => array('ext' => array("7z", "ace", "alz", "at3", "bke", "arc", "dds", "arj", "big", "bkf", "bzip2", "cab", "cpt", "sea", "daa", "deb", "dmg", "eea", "egt", "ecab", "ess", "gho", "gzip", "jar", "lbr", "lqr", "lha", "lzo", "lzx", "bin", "pak", "par", "par2", "pk4", "rar", "sit", "sitx", "tar", "gz", "tb", "tib", "uha", "vsa", "z", "zoo", "zip", "torrent")
                    ),
        'Program' => array('ext' => array("class", "com", "exe", "jar", "dll", "ocx")
                    )
        ),
    'info' => array(
        'audio:artist' => '%1 interpreta',
        'audio:title' => 'la canción %1',
        'audio:album' => 'del álbum %1',
        'audio:seconds' => 'con una duración de %1 segundos.'
    )
);


foreach ($content['types'] as $type => $info)
{
    $content['types'][$type]['crcExt'] = array();
    foreach ($info['ext'] as $ext)
    {
        $crc = crc32($ext);
        if ($crc<0) $crc+=4294967266;
        else $crc+=34;
        $content['types'][$type]['crcExt'] []= $crc;
    }
}