<?php
require_once APPLICATION_PATH . '/models/Files.php';

function formatSize($bytes)
{
    $size = $bytes / 1024;
    if($size < 1024)
    {
        $size = number_format($size, 2);
        $size .= ' KB';
    }
    else
    {
        if ($size / 1024 < 1024)
        {
            $size = number_format($size / 1024, 2);
            $size .= ' MB';
        }
        else if ($size / 1024 / 1024 < 1024)
        {
            $size = number_format($size / 1024 / 1024, 2);
            $size .= ' GB';
        }
    }
    return $size;
}

function show_matches($text, $words, &$found = null)
{
    $res = $text;
    foreach ($words as $w)
    {
        if ($w!='') $res = preg_replace("/\b($w)\b/i", "<b>$1</b>", $res, -1,$found);
    }
    $found = $found>0;
    return $res;
}

class Sphinx_Paginator implements Zend_Paginator_Adapter_Interface {
    public function __construct($table, $conditions = array())
    {
        if(!is_array($conditions) AND !empty($conditions))
            $conditions = array( $conditions );

        $this->conditions = $conditions;
        $this->table      = $table;

        require_once ( APPLICATION_PATH . '../../library/Sphinx/sphinxapi.php' );

        $sphinxConf =  new Zend_Config_Ini( APPLICATION_PATH . '/configs/application.ini' , 'production'  );
        $sphinxServer = $sphinxConf->sphinx->server;

        $this->cl = new SphinxClient();
        $this->cl->SetServer( $sphinxServer, 3312 );
        $this->cl->SetMatchMode( SPH_MATCH_EXTENDED2 );
        $this->cl->SetRankingMode( SPH_RANK_PROXIMITY );
        $this->cl->SetFieldWeights(array('metadata' => 10, 'filename' => 1));
        $this->cl->SetSelect("*, sum(@weight*(@weight+isources)/ln(fnCount+1)) as fileWeight");
        $this->cl->SetSortMode( SPH_SORT_EXTENDED, "fnWeight DESC, isources DESC" );
        $this->cl->SetGroupBy( "idfile", SPH_GROUPBY_ATTR, "fileWeight DESC, isources DESC, fnCount DESC");
        $this->cl->SetMaxQueryTime(500);
        $this->tcount = 0;

        
    }
    
    public function getItems($offset, $itemCountPerPage)
    {
        global $content;

        $this->cl->SetLimits( $offset, $itemCountPerPage);
        $type = $this->conditions['type'];
        $typeCrcs = null;
        if ($type)
        {
            $temp = $content['types'][$type];
            if ($temp) {
                $typeCrcs = $temp['crcExt'];
            }
        }

        if ($typeCrcs) $this->cl->SetFilter('crcextension', $typeCrcs);

        $src = $this->conditions['src'];
        if ($src)
        {
            $srcs = array();
            foreach (str_split($src) as $s)
            {
                $srcs = array_merge($srcs, $content['sources'][$s]['types']);
            }
            
            if (count($srcs)>0)
                $this->cl->SetFilter('types', $srcs);
        }

        $size = $this->conditions['size'];
        if ($size)
        {
            switch ($size)
            {
                case 1:
                    $this->cl->SetFilterRange('size', 1, 1048576);
                    break;
                case 2:
                    $this->cl->SetFilterRange('size', 1, 10485760);
                    break;
                case 3:
                    $this->cl->SetFilterRange('size', 1, 104857600);
                    break;
                case 4:
                    $this->cl->SetFilterRange('size', 0, 104857600, true);
                    break;
            }
        }

        $brate = $this->conditions['brate'];
        if ($brate)
        {
            $brateCode = 2<<22;
            $maxBrateCode = $brateCode|1000000;
            switch ($brate)
            {
                case 1:
                    $this->cl->SetFilterRange('metadatas', $brateCode|128, $maxBrateCode);
                    break;
                case 2:
                    $this->cl->SetFilterRange('metadatas', $brateCode|192, $maxBrateCode);
                    break;
                case 3:
                    $this->cl->SetFilterRange('metadatas', $brateCode|256, $maxBrateCode);
                    break;
                case 4:
                    $this->cl->SetFilterRange('metadatas', $brateCode|320, $maxBrateCode);
                    break;
            }
        }

        $year = $this->conditions['year'];
        if ($year)
        {
            $yearCode = 1<<22;
            switch ($year)
            {
                case 1:
                    $this->cl->SetFilterRange('metadatas', $yearCode|1900, $yearCode|1959);
                    break;
                case 2:
                    $this->cl->SetFilterRange('metadatas', $yearCode|1960, $yearCode|1969);
                    break;
                case 3:
                    $this->cl->SetFilterRange('metadatas', $yearCode|1970, $yearCode|1979);
                    break;
                case 4:
                    $this->cl->SetFilterRange('metadatas', $yearCode|1980, $yearCode|1989);
                    break;
                case 5:
                    $this->cl->SetFilterRange('metadatas', $yearCode|1990, $yearCode|1999);
                    break;
                case 6:
                    $this->cl->SetFilterRange('metadatas', $yearCode|2000, $yearCode|2010);
                    break;
                case 7:
                    $nowy = (int)date('Y');
                    $this->cl->SetFilterRange('metadatas', $yearCode|($nowy-1), $yearCode|$nowy);
                    break;
            }
        }

        $query = $this->conditions['query'];
        $result = $this->cl->Query( "$query", $this->table );

        $words = explode(" ", $query);

        if ( $result === false  ) {
               // echo "Query failed: " . $this->cl->GetLastError() . ".\n";
        } else {
                if ( $this->cl->GetLastWarning() ) {
                  //echo "WARNING: " . $this->cl->GetLastWarning() . "";
              }
                $this->tcount = $result["total_found"];
                $this->time_desc = $result["time"];
                $total_time = $result["time"];
                if ( ! empty($result["matches"]) ) {
                        $ids = array();
                        $idfns = array();

                        foreach ( $result["matches"] as $doc => $docinfo )
                        {
                            $id = $docinfo["attrs"]["idfile"];
                            $ids []= $id;
                            $idfns []= $doc;

                            $docs[$id]['weight'] = $docinfo["weight"];
                            $docs[$id]['fileweight'] = $docinfo["attrs"]["fileweight"];
                            $docs[$id]['attrs'] = $docinfo["attrs"];
                            $docs[$id]['idfilename'] = $doc;
                            $md[$id] = array();

                            if ($type==null)
                            {
                                try {
                                    $docs[$id]['type'] = $content['assoc'][$docinfo["attrs"]["contenttype"]];
                                } catch (Exception $ex) {
                                    $docs[$id]['type'] = null;
                                    $docs[$id]['type_prop'] = array();
                                }
                            } else {
                                $docs[$id]['type'] = $type;
                            }

                            $where .= " OR (IdFilename=$doc AND IdFile=$id) ";
                        }

                        $ids = join($ids, ",");

                        $start_time = microtime(true);
                        $fn_model = new ff_filename();
                        foreach ($fn_model->fetchAll(substr($where, 4)) as $row)
                        {
                            $id = $row['IdFile'];
                            // try to guess type from extensionss
                            if ($docs[$id]['type']==null)
                            {
                                try {$docs[$id]['type_prop'] []= $content['extAssoc'][$row['Extension']];}
                                catch (Exception $ex) {};
                            }

                            $docs[$id]['rfilename'] = htmlentities($row['Filename'], ENT_QUOTES);
                            $docs[$id]['filename'] = show_matches($row['Filename'], $words, $found);
                            $docs[$id]['in_filename'] = $found;
                        }
                        $total_time += (microtime(true) - $start_time);
                        $this->time_desc .= " - ".(microtime(true) - $start_time);
                        
                        // get sources for files
                        $start_time = microtime(true);
                        $sources = new ff_sources();
                        $sourcepos = array();
                        foreach ($sources->fetchAll("IdFile in ($ids)") as $row)
                        {
                            $id = $row['IdFile'];
                            $t = $row['Type'];
                            switch ($t)
                            {
                                case 1: //GNUTELLA
                                    $tip = "MagnetLink";
                                    $source = "magnet";
                                    $rlink = "magnet:?dt=".$docs[$id]['rfilename']."&xt=urn:sha1:".$row['Uri'];
                                    $link = "magnet:?dt=".$docs[$id]['filename']."&xt=urn:sha1:".$row['Uri'];
                                    break;
                                case 2: //ED2K
                                    $tip = "ED2K";
                                    $source = "ed2k";
                                    $rlink = "ed2k://|file|".$docs[$id]['rfilename']."|".$docs[$id]['attrs']['size']."|".$row['Uri'];
                                    $link = "ed2k://|file|".$docs[$id]['filename']."|".$docs[$id]['attrs']['size']."|".$row['Uri'];
                                    break;
                                case 3:
                                    $tip = "BitTorrent";
                                    $source = "torrent";
                                    $rlink = $link = $row['Uri'];
                                    break;
                                case 6: //MD5 HASH
                                    $tip = "MagnetLink";
                                    $source = "magnet";
                                    $rlink = "magnet:?dt=".$docs[$id]['rfilename']."&xt=urn:md5:".$row['Uri'];
                                    $link = "magnet:?dt=".$docs[$id]['filename']."&xt=urn:md5:".$row['Uri'];
                                    break;
                                case 7: //BTH HASH
                                    $tip = "MagnetLink";
                                    $source = "magnet";
                                    $rlink = "magnet:?dt=".$docs[$id]['rfilename']."&xt=urn:bth:".$row['Uri'];
                                    $link = "magnet:?dt=".$docs[$id]['filename']."&xt=urn:bth:".$row['Uri'];
                                    break;
                                case 4: // JAMENDO
                                case 8: // WEB
                                    $tip = "Web";
                                    $source = "web";
                                    $rlink = $link = $row['Uri'];
                                    break;
                                case 9: // FTP
                                    $tip = "FTP";
                                    $source = "ftp";
                                    $rlink = $link = $row['Uri'];
                                    break;
                                default:
                                    continue;
                                    break;
                            }
                            $newpos = array_search($t, $srcs);
                            if ($newpos!==false && (!isset($sourcepos[$id]) || ($newpos<$sourcepos[$id])))
                            {
                                $sourcepos[$id] = $newpos;
                                $docs[$id]['rlink'] = $rlink;
                                $docs[$id]['link'] = $link;
                                $docs[$id]['link_type'] = $t;
                            }
                            if ($source) {
                                $docs[$id]['sources'][$source]['rlink'] = $rlink;
                                $docs[$id]['sources'][$source]['count'] += $row['MaxSources'];
                                $docs[$id]['sources'][$source]['tip'] = $tip;
                            }
                        }
                        $total_time += (microtime(true) - $start_time);
                        $this->time_desc .= " - ".(microtime(true) - $start_time);
                        // get metadata for files
                        $start_time = microtime(true);
                        
                        $metadata = new ff_metadata();
                        foreach ($metadata->fetchAll("CrcKey in (".join($content['crcMD'], ",").") AND IdFile in ($ids)") as $row)
                        {
                            if ($docs[$id]['link_type']==7 && $row['KeyMD']=='torrent:trackers'||$row['KeyMD']=='torrent:tracker')
                            {
                                foreach (explode(' ', $row['ValueMD']) as $tr)
                                {
                                    $docs[$id]['sources']['magnet']['rlink'] .= '&tr='.urlencode($tr);
                                    $docs[$row['IdFile']]['link'] .= '&tr='.urlencode($tr);
                                    $docs[$row['IdFile']]['rlink'] .= '&tr='.urlencode($tr);
                                }
                            }
                            else
                                $md[$row['IdFile']][$row['KeyMD']]=show_matches($row['ValueMD'], $words);

                        }
                        $total_time += (microtime(true) - $start_time);
                        $this->time_desc .= " - ".(microtime(true) - $start_time);

                        // choose better type for each file and get description for file
                        $start_time = microtime(true);
                        foreach ($docs as $id => $doc)
                        {
                            if ($doc['type']==null && count($doc['type_prop'])>0)
                            {
                                // TODO: count each option and choose better
                                $docs[$id]['type'] = $doc['type_prop'][0];
                            }

                            if ($doc['attrs']['size']>0) $docs[$id]['size'] = formatSize($doc['attrs']['size']);
                            $docs[$id]['isources'] = $doc['attrs']['isources'];
                            $docs[$id]['md'] = $md[$id];
                        }
                        
                        $total_time += (microtime(true) - $start_time);
                        $this->time_desc .= " - ".(microtime(true) - $start_time);
                        $this->time = $total_time;
                        unset($this->cl); //this unset frees memory use
                        unset ($doc);
                        return $docs;
                }
        }
       
       return array();
    }

    public function count()
    {
        return $this->tcount;
    }
}

class SearchController extends Zend_Controller_Action {

    public function init() {

        $request = $this->getRequest ();

        $requesttitle .= ' '.$this->_xss_clean($this->_getParam('q'));
        $this->view->headTitle()->append(' - ');
        $this->view->headTitle()->append($requesttitle);
    }


    public function indexAction() {


        $qw = $this->_getParam('q');
        $type = $this->_getParam('type');
        $page = $this->_getParam('page', 1);
        $src = $this->_getParam('src');
        $opt = $this->_getParam('opt')=='1';
        $size = $this->_getParam('size');
        $year = $this->_getParam('year');
        $brate = $this->_getParam('brate');

        

        // Create a filter chain and add filters
        $encoding = array('quotestyle' => ENT_QUOTES, 'charset' => 'UTF-8');

        $f = new Zend_Filter();
        $f->addFilter(new Zend_Filter_HtmlEntities($encoding));
        $f->addFilter(new Zend_Filter_StringTrim());
        $f->addFilter(new Zend_Filter_StripTags($encoding));
       
        

        $q = $f->filter ( $qw );
        $type = $f->filter ( $type );
        $src = $f->filter ( $src );
        $size = $f->filter ( $size );
        $opt = $f->filter ( $opt );
        $year = $f->filter ( $year );
        $brate = $f->filter ( $brate );



        $form = $this->_getSearchForm();


 if (!$q) { // check if query search is empty

            $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Hey! Write something' ) );
            $this->_redirect ( '/' );
            return ;
        }



        $form->getElement('q')->setValue(trim($qw));

        $form->loadDefaultDecoratorsIsDisabled(false);

        $form->addElement("hidden", "type", array("value"=>$type));
        $form->addElement("hidden", "src", array("value"=>$src));
        $form->addElement("hidden", "opt", array("value"=>$opt));
        
        foreach($form->getElements() as $element) {
            $element->removeDecorator('DtDdWrapper');
            $element->removeDecorator('Label');
        }
                
        // assign the form to the view
        $this->view->form = $form;

        $srcs = array();
        $src2 = ($src=='')?'wftme':$src;
        $srcs['ed2k'] = (strpos($src2, 'e')===false)?$src.'e':str_replace('e', '', $src2);
        $srcs['magnet'] = (strpos($src2, 'm')===false)?$src.'m':str_replace('m', '', $src2);
        $srcs['torrent'] = (strpos($src2, 't')===false)?$src.'t':str_replace('t', '', $src2);
        $srcs['web'] = (strpos($src2, 'w')===false)?$src.'w':str_replace('w', '', $src2);
        $srcs['ftp'] = (strpos($src2, 'f')===false)?$src.'f':str_replace('f', '', $src2);

        require_once APPLICATION_PATH.'/views/helpers/QueryString_View_Helper.php';
        $helper = new QueryString_View_Helper();
        $helper->setParams(array('q'=>trim($q), 'type'=>$type, 'page'=>$page, 'src'=>$src2, 'opt'=>$opt, 'size' => $size, 'year' => $year, 'brate' => $brate));
        
        $this->view->registerHelper($helper, 'qs');
        $this->view->src = $srcs;


        $SphinxPaginator = new Sphinx_Paginator('idx_files',array('query'=>$qw, 'src'=>$src2, 'type'=>$type, 'size' => $size, 'year' => $year, 'brate' => $brate));

        if ($SphinxPaginator !== null) {
                //paginator
                $paginator = new Zend_Paginator($SphinxPaginator);
                $paginator->setDefaultScrollingStyle('Elastic');
                $paginator->setItemCountPerPage(10);
               
                //setting the paginator cache 
                $fO = array('lifetime' => 3600, 'automatic_serialization' => true);
                $bO = array('cache_dir'=>'/tmp');
                $cache = Zend_Cache::factory('Core', 'File', $fO, $bO);

                $paginator->setCache($cache);
                $paginator->setCurrentPageNumber($page);

                $paginator->getCurrentItems();
                $this->view->info = array('total'=>$SphinxPaginator->tcount, 'time_desc'=>$SphinxPaginator->time_desc, 'time'=>$SphinxPaginator->time, 'q' => $q, 'start' => 1+($page-1)*10, 'end' => min($SphinxPaginator->tcount, $page*10));

                $this->view->paginator = $paginator;
        }

//        $jquery = $this->view->jQuery();
//        $jquery->enable(); // enable jQuery Core Library
//
//        // get current jQuery handler based on noConflict settings
//        $jqHandler = ZendX_JQuery_View_Helper_JQuery::getJQueryHandler();
//        $onload = '("#show_options").click(function() '
//                  . '{'
//                  . '   active = $("#show_options").attr("active")=="1";'
//                  . '   switchOptions(active, true);'
//                  . '});'
//                  . ' switchOptions('.($opt?'false':'true').', false);';
//
//        $function = 'function switchOptions(active, fade) {'
//                  . '   if (active) {'
//                  . '       $("#results").removeClass("padding");'
//                  . '       $("#options").toggle(false);'
//                  . '       $("#show_options").text("'.$this->view->translate('Show options...').'");'
//                  . '   } else {'
//                  . '       $("#results").addClass("padding");'
//                  . '       if (fade) $("#options").fadeIn(); else $("#options").toggle(true);'
//                  . '       $("#show_options").text("'.$this->view->translate('Hide options').'");'
//                  . '   } $("#show_options").attr("active", 1-(active?1:0));'
//                  . '}';
//        $jquery->addJavascript($function);
//        $jquery->addOnload($jqHandler . $onload);

    }

        /**
         *
         * @return Form_Search
         */
        protected function _getSearchForm() {
                require_once APPLICATION_PATH . '/forms/Search.php';
                $form = new Form_Search( );

                return $form;
        }



        protected function _xss_clean($data)
        {
        // Fix &entity\n;
        $data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
        $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
        $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
        //$data = html_entity_decode($data);


        $data = strip_tags($data);
        $data = htmlentities($data, ENT_QUOTES, 'UTF-8');
        // Remove any attribute starting with "on" or xmlns
        $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

        // Remove javascript: and vbscript: protocols
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

        // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

        // Remove namespaced elements (we do not need them)
        $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

        do
        {
                // Remove really unwanted tags
                $old_data = $data;
                $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
        }
        while ($old_data !== $data);

        // we are done...
        return $data;
        }



}

