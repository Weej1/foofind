<?php
require_once APPLICATION_PATH . '/models/Files.php';

define(MAX_RESULTS, 1000);

function encodeFilename($filename)
{
    return str_replace(" ", "+", $filename);
}

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

function show_matches($text, $query, $url, &$found = null)
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

class Sphinx_Paginator implements Zend_Paginator_Adapter_Interface {
    public function __construct($table)
    {
        $this->table      = $table;
        
        require_once ( APPLICATION_PATH . '../../library/Sphinx/sphinxapi.php' );
        $sphinxConf = new Zend_Config_Ini( APPLICATION_PATH . '/configs/application.ini' , 'production'  );
        $sphinxServer = $sphinxConf->sphinx->server;

        $this->tcount = 0;

        $this->cl = new SphinxClient();
        $this->cl->SetServer( $sphinxServer, 3312 );
        $this->cl->SetMatchMode( SPH_MATCH_EXTENDED2 );
        $this->cl->SetRankingMode( SPH_RANK_PROXIMITY );
        $this->cl->SetFieldWeights(array('metadata' => 10, 'filename' => 1));
        $this->cl->SetSelect("*, sum(@weight*isources*sources/fnCount) as fileWeight");
        $this->cl->SetSortMode( SPH_SORT_EXTENDED, "@weight DESC, fnWeight DESC, isources DESC" );
        $this->cl->SetGroupBy( "idfile", SPH_GROUPBY_ATTR, "fileWeight DESC, isources DESC, fnCount DESC");
        $this->cl->SetMaxQueryTime(500);
    }
    public function setFilters($conditions)
    {
        global $content;

        if(!is_array($conditions) AND !empty($conditions))
            $conditions = array( $conditions );

        $this->cl->ResetFilters();
        $this->cl->SetFilter('blocked', array(0));

        $this->type = $conditions['type'];
        $typeCrcs = null;
        if ($this->type)
        {
            $temp = $content['types'][$this->type];
            if ($temp) {
                $typeCrcs = $temp['crcExt'];
            }
        }

        if ($typeCrcs) $this->cl->SetFilter('crcextension', $typeCrcs);

        $this->src = $conditions['src'];
        if ($this->src)
        {
            $this->srcs = array();
            foreach (str_split("wftge") as $s)
            {
                if (strstr($this->src, $s)) $this->srcs = array_merge($this->srcs, $content['sources'][$s]['types']);
            }

            if (count($this->srcs)>0)
                $this->cl->SetFilter('types', $this->srcs);
        }

        $this->size = $conditions['size'];
        if ($this->size)
        {
            switch ($this->size)
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

        $this->brate = $conditions['brate'];
        if ($this->brate)
        {
            $brateCode = 2<<22;
            $maxBrateCode = $brateCode|1000000;
            switch ($this->brate)
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

        $this->year = $conditions['year'];
        if ($this->year)
        {
            $yearCode = 1<<22;
            switch ($this->year)
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

        $this->query = $conditions['q'];
    }

    public function justCount()
    {
        $start_time = microtime(true);
        $this->cl->SetLimits( 0, 1, 1);
        $this->cl->SetMaxQueryTime(100);

        $result = $this->cl->Query( $this->query, $this->table );

        $this->time += (microtime(true) - $start_time);
        $this->time_desc .= " - ".(microtime(true) - $start_time);

        if ( $result === false )
            return null;
        else
            return $result['total_found'];
    }

    public function getItems($offset, $itemCountPerPage)
    {
        global $content;
        $this->cl->SetLimits( $offset, $itemCountPerPage, MAX_RESULTS);
        $result = $this->cl->Query( $this->query, $this->table );

        if ( $result === false  ) {
               // echo "Query failed: " . $this->cl->GetLastError() . ".\n";
        } else {
                if ( $this->cl->GetLastWarning() ) {
                  //echo "WARNING: " . $this->cl->GetLastWarning() . "";
                }
                $this->tcount = $result["total_found"];
                $this->time_desc = "spx: ".$result["time"];
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

                            if ($this->type==null)
                            {
                                try {
                                    $docs[$id]['type'] = $content['assoc'][$docinfo["attrs"]["contenttype"]];
                                } catch (Exception $ex) {
                                    $docs[$id]['type'] = null;
                                    $docs[$id]['type_prop'] = array();
                                }
                            } else {
                                $docs[$id]['type'] = $this->type;
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

                            $docs[$id]['rfilename'] = $row['Filename'];
                            $docs[$id]['filename'] = show_matches(htmlentities($row['Filename'], ENT_QUOTES, "UTF-8"), $this->query, false, $found);
                            $docs[$id]['in_filename'] = $found;
                        }
                        $total_time += (microtime(true) - $start_time);
                        $this->time_desc .= " - fn:".number_format(microtime(true) - $start_time, 3);
                        
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
                                    $tip = "Gnutella";
                                    $source = "gnutella";
                                    $mlinkadd = "&xt=urn:sha1:".$row['Uri'];
                                    break;
                                case 2: //ED2K
                                    $tip = "ED2K";
                                    $source = "ed2k";
                                    $link = "ed2k://|file|".encodeFilename($docs[$id]['rfilename'])."|".$docs[$id]['attrs']['size']."|".$row['Uri']."|/";
                                    //$mlinkadd = "&xt=urn:ed2k:".$row['Uri'];
                                    break;
                                case 3: // TORRENT
                                    $tip = "Torrent";
                                    $source = "torrent";
                                    $link = $row['Uri'];
                                    break;
                                case 5: //TIGER HASH
                                    $tip = "Gnutella";
                                    $source = "gnutella";
                                    $mlinkadd = "&xt=urn:tiger:".$row['Uri'];
                                    break;
                                case 6: //MD5 HASH
                                    $tip = "Gnutella";
                                    $source = "gnutella";
                                    $mlinkadd = "&xt=urn:md5:".$row['Uri'];
                                    break;
                                case 7: //BTH HASH
                                    $tip = "Torrent MagnetLink";
                                    $source = "tmagnet";
                                    $link = "magnet:?xl=".$docs[$id]['attrs']['size']."&dn=".encodeFilename($docs[$id]['rfilename'])."&xt=urn:btih:".$row['Uri'];
                                    break;
                                case 4: // JAMENDO
                                case 8: // WEB
                                    $tip = "Web";
                                    $source = "web";
                                    $link = $row['Uri'];
                                    break;
                                case 9: // FTP
                                    $tip = "FTP";
                                    $source = "ftp";
                                    $link = $row['Uri'];
                                    break;
                                default:
                                    continue;
                                    break;
                            }

                            if ($source=="gnutella")
                            {
                                $rlink = $docs[$id]['sources']['gnutella']['rlink'];
                                if ($rlink)
                                    $mlink = $rlink.$mlinkadd;
                                else
                                    $mlink = "magnet:?xl=".$docs[$id]['attrs']['size']."&dn=".encodeFilename($docs[$id]['rfilename']).$mlinkadd;
                                $link = $mlink;
                            }

                            $docs[$id]['sources'][$source]['link'] = htmlentities($link, ENT_QUOTES, "UTF-8");
                            $docs[$id]['sources'][$source]['rlink'] = $link;
                            $docs[$id]['sources'][$source]['count'] += $row['MaxSources'];
                            $docs[$id]['sources'][$source]['tip'] = $tip;
                        }

                        $total_time += (microtime(true) - $start_time);
                        $this->time_desc .= " - src:".number_format(microtime(true) - $start_time, 3);
                        // get metadata for files
                        $start_time = microtime(true);
                        
                        $metadata = new ff_metadata();
                        // search for shown metadata
                        $mdList = join($content['crcMD'], ",");
                        // add bittorrent metadata
                        $mdList .= ", 4009003051, 4119033687";
                        
                        foreach ($metadata->fetchAll("CrcKey in ($mdList) AND IdFile in ($ids)") as $row)
                        {
                            $id = $row['IdFile'];
                            if (($row['KeyMD']=='torrent:trackers') || ($row['KeyMD']=='torrent:tracker'))
                            {
                                foreach (explode(' ', $row['ValueMD']) as $tr)
                                {
                                    $docs[$id]['sources']['tmagnet']['rlink'] .= '&tr='.urlencode($tr);
                                    $docs[$id]['sources']['tmagnet']['link'] = htmlentities($docs[$id]['sources']['tmagnet']['rlink'], ENT_QUOTES, "UTF-8");
                                    $docs[$id]['sources']['tmagnet']['has_trackers'] = true;
                                }
                            }
                            else
                                $md[$id][$row['KeyMD']]=show_matches(htmlentities($row['ValueMD'], ENT_QUOTES, "UTF-8"), $this->query, false);
                            
                        }
                        $total_time += (microtime(true) - $start_time);
                        $this->time_desc .= " - md:".number_format(microtime(true) - $start_time, 3);

                        // choose better type for each file and get description for file
                        $start_time = microtime(true);
                        foreach ($docs as $id => $doc)
                        {

                            //replace dot by underscore remove extension to filename in the url (google bot thinks its a image or a video, this is bad)
                            //$docs[$id]['rfilename'] = str_replace('.', '_', $docs[$id]['rfilename']);
                            $docs[$id]['rfilename'] = $docs[$id]['rfilename'].'.html';

                            $docs[$id]['dlink'] = "$id/{$docs[$id]['rfilename']}"; //

                            if (!$doc['filename']) {
                                $this->cl->UpdateAttributes("idx_files idx_files_week", array("blocked"), array($docs[$id]['idfilename'] => array(3)));
                                $this->tcount--;
                                continue;
                            }
                            
                            if ($doc['type']==null && count($doc['type_prop'])>0)
                            {
                                $docs[$id]['type'] = $doc['type_prop'][0];
                            }

                            if ($doc['attrs']['size']>0) $docs[$id]['size'] = formatSize($doc['attrs']['size']);
                            $docs[$id]['isources'] = $doc['attrs']['isources'];
                            $docs[$id]['md'] = $md[$id];
                            
                            // search for better link
                            foreach (array('w'=>'web', 'f'=>'ftp', 't'=>'torrent', 't2'=>'tmagnet', 'g'=>'gnutella', 'e'=>'ed2k') as $srci=>$srcLink)
                                    if (strstr($this->src, $srci[0]) && $docs[$id]['sources'][$srcLink])
                                            if ($srcLink!='tmagnet' || $docs[$id]['sources']['tmagnet']['has_trackers'])
                                                break;

                            $docs[$id]['rlink'] = htmlentities($docs[$id]['sources'][$srcLink]['rlink'], ENT_QUOTES, "UTF-8");
                            
                            $docs[$id]['link'] = show_matches($docs[$id]['rlink'], $this->query, true);
                            $docs[$id]['link_type'] = $srcLink;
                        }
                        
                        unset ($doc);
                        $total_time += (microtime(true) - $start_time);
                        $this->time = $total_time;
                        return $docs;
                }
        }

        $this->time = 0;
       return array();
    }

    public function count()
    {
        return min($this->tcount, MAX_RESULTS);
    }
}

class SearchController extends Zend_Controller_Action {

    public function init() {

        $this->_flashMessenger = $this->_helper->getHelper ( 'FlashMessenger' );
        $this->view->mensajes = $this->_flashMessenger->getMessages ();

    }


    public function indexAction() {


        $this->view->lang =  $this->_helper->checklang->check();

        $qw = stripcslashes(strip_tags($this->_getParam('q')));
        $type = $this->_getParam('type');
        $page = $this->_getParam('page', 1);
        $src = $this->_getParam('src');
        $opt = $this->_getParam('opt');
        $size = $this->_getParam('size');
        $year = $this->_getParam('year');
        $brate = $this->_getParam('brate');

        // Create a filter chain and add filters
        $encoding = array('quotestyle' => ENT_QUOTES, 'charset' => 'UTF-8');

        $f = new Zend_Filter();
        //$f->addFilter(new Zend_Filter_HtmlEntities($encoding));
        $f->addFilter(new Zend_Filter_StringTrim());
        $f->addFilter(new Zend_Filter_StripTags($encoding));

        $q = $f->filter ( trim($qw ));
        $type = $f->filter ( $type );
        $src = $f->filter ( $src );
        $opt = $f->filter ( $opt );
        $size = $f->filter ( $size );
        $year = $f->filter ( $year );
        $brate = $f->filter ( $brate );

        if (!preg_match("/^Audio$/", $type)) $brate = null;
        if (!preg_match("/^Audio|Video$/", $type)) $year= null;

        $this->view->headTitle()->append(' - ');
        $this->view->headTitle()->append($qw);

        $form = $this->_getSearchForm();
         if (!$q) { // check if query search is empty

            $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Hey! Write something' ) );
            $this->_redirect ( '/' );
            return ;
        }

        $form->getElement('q')->setValue(trim($q));

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

        if(!$src)
        {
            if ($_COOKIE['src']) $src = $_COOKIE['src'];
        } else {
            setcookie( 'src', $src, null, '/' );
        }
        $srcs = array();
        $src2 = ($src=='')?'wftge':$src;
        $srcs['ed2k'] = (strpos($src2, 'e')===false)?$src.'e':str_replace('e', '', $src2);
        $srcs['gnutella'] = (strpos($src2, 'g')===false)?$src.'g':str_replace('g', '', $src2);
        $srcs['torrent'] = (strpos($src2, 't')===false)?$src.'t':str_replace('t', '', $src2);
        $srcs['web'] = (strpos($src2, 'w')===false)?$src.'w':str_replace('w', '', $src2);
        $srcs['ftp'] = (strpos($src2, 'f')===false)?$src.'f':str_replace('f', '', $src2);

        require_once APPLICATION_PATH.'/views/helpers/QueryString_View_Helper.php';

        $conds = array('q'=>trim($q), 'src'=>$src2, 'opt'=>$opt, 'type'=>$type, 'size' => $size, 'year' => $year, 'brate' => $brate, 'page' => $page);

        $helper = new QueryString_View_Helper();
        $helper->setParams($conds);
        
        $this->view->registerHelper($helper, 'qs');
        $this->view->src = $srcs;
        $this->view->qs = $conds;

        if ($page>MAX_RESULTS/10)
        {
            $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Foofind does not allow browse results after page 1000.' ) );
            $this->_redirect("/{$this->view->lang}/search/".$helper->qs(array(), array('page'=>1)));
            return;
        }
        $SphinxPaginator = new Sphinx_Paginator('idx_files, idx_files_week');
        $SphinxPaginator->setFilters($conds);

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

                if ($conds['type']!=null && $SphinxPaginator->count()==0)
                {
                    $conds['type']=null;
                    $SphinxPaginator->setFilters($conds);
                    $noTypeCount = $SphinxPaginator->justCount();
                }
                
                $this->view->info = array('total'=>$SphinxPaginator->tcount, 'time_desc'=>$SphinxPaginator->time_desc, 'time'=>$SphinxPaginator->time, 'q' => $q, 'start' => 1+($page-1)*10, 'end' => min($SphinxPaginator->tcount, $page*10), 'notypecount' => $noTypeCount);
                $this->view->paginator = $paginator;
        }

        $jquery = $this->view->jQuery();
        $jquery->enable(); // enable jQuery Core Library

        // get current jQuery handler based on noConflict settings
        $jqHandler = ZendX_JQuery_View_Helper_JQuery::getJQueryHandler();
        $onload = '("#show_options").click(function() '
                  . '{'
                  . '   active = $("#show_options").attr("active")=="1";'
                  . '   switchOptions(active, true);'
                  . '});'
                  . ' switchOptions('.($opt?'false':'true').', false);';

        $function = 'function switchOptions(active, fade) {'
                  . '   if (active) {'
                  . '       $("#results").removeClass("padding");'
                  . '       $("#advsearch").toggle(false);'
                  . '       $("#show_options").text("'.$this->view->translate('advanced search').'");'
                  . '   } else {'
                  . '       $("#results").addClass("padding");'
                  . '       if (fade) $("#advsearch").fadeIn(); else $("#advsearch").toggle(true);'
                  . '       $("#show_options").text("'.$this->view->translate('hide advanced search').'");'
                  . '   } $("#show_options").attr("active", 1-(active?1:0));'
                  . '}';
        $jquery->addJavascript($function);
        $jquery->addOnload($jqHandler . $onload);

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




}

