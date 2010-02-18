<?php

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

function show_matches($text, $words)
{
    $res = $text;
    foreach ($words as $w)
    {
        $res = preg_replace("/($w)/i", "<b>$1</b>", $res);
    }
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
        $this->cl->SetMatchMode( SPH_MATCH_ALL );
        $this->cl->SetRankingMode( SPH_RANK_PROXIMITY_BM25 );
        $this->cl->SetSelect("*, sum(isources*@weight/fnCount) as fileWeight");
        $this->cl->SetSortMode( SPH_SORT_EXTENDED, "isources DESC, fnWeight DESC, isources DESC" );
        $this->cl->SetGroupBy( "idfile", SPH_GROUPBY_ATTR, "fileWeight DESC");
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
            $srcs = $content['sources'][$src];
            if ($srcs)
                $this->cl->SetFilter('types', $srcs['types']);
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

        $query = $this->conditions['query'];
        $result = $this->cl->Query( $query, $this->table );

        $words = explode(" ", $query);

        if ( $result === false  ) {
                echo "Query failed: " . $this->cl->GetLastError() . ".\n";
        } else {
                if ( $this->cl->GetLastWarning() ) {
                  echo "WARNING: " . $this->cl->GetLastWarning() . "";
              }
                $this->tcount = $result["total_found"];
                $this->time = $result["time"];

                if ( ! empty($result["matches"]) ) {
                        $ids= array();
                        $idfns = array();
                        
                        foreach ( $result["matches"] as $doc => $docinfo )
                        {
                            $id = $docinfo["attrs"]["idfile"];
                            $ids []= $id;
                            $idfns []= $doc;

                            $docs[$id]['attrs'] = $docinfo["attrs"];
                            $docs[$id]['idfilename'] = $doc;
                            $md[$id] = array();

                            if ($type==null)
                            {
                                try {
                                    $docs[$id]['type'] = $content['assoc'][$docinfo["attrs"]["contentType"]];
                                } catch (Exception $ex) {
                                    $docs[$id]['type'] = null;
                                    $docs[$id]['type_prop'] = array();
                                }
                            } else {
                                $docs[$id]['type'] = $type;
                            }
                        }

                        $ids = join($ids, ",");
                        
                        // Browse filenames
                        $fn_model = new Zend_Db_Table('ff_filename');
                        $filenames = $fn_model->fetchAll("IdFilename in (".join($idfns,',').")");

                        foreach ($filenames as $row)
                        {
                            $id = $row['IdFile'];

                            // try to guess type from extensionss
                            if ($docs[$id]['type']==null)
                            {
                                try {$docs[$id]['type_prop'] []= $content['extAssoc'][$row['Extension']];}
                                catch (Exception $ex) {};
                            }

                            // If is the filename returned by sphinx, get filename
                            if ($docs[$id]['idfilename']==$row['IdFilename'])
                            {
                                $docs[$id]['rfilename'] = $row['Filename'];
                                $docs[$id]['filename'] = show_matches($row['Filename'], $words);
                            }
                        }

                        // get sources for files
                        $sources = new Zend_Db_Table('ff_sources');
                        foreach ($sources->fetchAll("IdFile in ($ids)") as $row)
                        {
                            // TODO: choose better source for each file
                            // TODO: create e-link
                            if ($srcs && !in_array($row['Type'],$srcs['types'])) continue;
                            $id = $row['IdFile'];
                            switch ($row['Type'])
                            {
                                case 1: //GNUTELLA
                                    $source = "Gnutella";
                                    $rlink = "magnet:?dt=".$docs[$id]['rfilename']."&xt=urn:sha1:".$row['Uri'];
                                    $link = "magnet:?dt=".$docs[$id]['filename']."&xt=urn:sha1:".$row['Uri'];
                                    break;
                                case 2: //ED2K
                                    $source = "ED2K";
                                    $rlink = "ed2k://|file|".$docs[$id]['rfilename']."|".$docs[$id]['attrs']['size']."|".$row['Uri'];
                                    $link = "ed2k://|file|".$docs[$id]['filename']."|".$docs[$id]['attrs']['size']."|".$row['Uri'];
                                    break;
                                case 3:
                                    $source = "BitTorrent";
                                    $link = $row['Uri'];
                                    break;
                                case 6: //MD5 HASH
                                    $source = "Gnutella";
                                    $rlink = "magnet:?dt=".$docs[$id]['rfilename']."&xt=urn:md5:".$row['Uri'];
                                    $link = "magnet:?dt=".$docs[$id]['filename']."&xt=urn:md5:".$row['Uri'];
                                    break;
                                case 7: //BTH HASH
                                    $source = "BitTorrent";
                                    $rlink = "magnet:?dt=".$docs[$id]['rfilename']."&xt=urn:bth:".$row['Uri'];
                                    $link = "magnet:?dt=".$docs[$id]['filename']."&xt=urn:bth:".$row['Uri'];
                                    break;
                                default:
                                    $source = null;
                                    $link = show_matches($row['Uri'], $words);
                                    $rlink = $row['Uri'];
                                    break;

                            }
                            $docs[$id]['rlink'] = $rlink;
                            $docs[$id]['link'] = $link;
                            if ($source) $docs[$id]['sources'][$source] += $row['Sources'];
                        }

                        // get metadata for files
                        $metadata = new Zend_Db_Table('ff_metadata');
                        foreach ($metadata->fetchAll("IdFile in ($ids)") as $row)
                            $md[$row['IdFile']][$row['KeyMD']]=show_matches($row['ValueMD'], $words);

                        // choose better type for each file and get description for file
                        foreach ($docs as $id => $doc)
                        {
                            if ($doc['type']==null && count($doc['type_prop'])>0)
                            {
                                // TODO: count each option and choose better
                                $docs[$id]['type'] = $doc['type_prop'][0];
                            }

                            if ($doc['attrs']['size']>0) $docs[$id]['size'] = formatSize($doc['attrs']['size']);
                            $docs[$id]['isources'] = $doc['attrs']['isources'];
                            try { 
                                $func = 'format'.$docs[$id]['type'];
                                if ($func) $docs[$id]['info'] = $func($md[$id]);}
                            catch (Exception $ex) {}
                        }

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

        $requesttitle .= ' '.$this->_getParam('q');
        $this->view->headTitle()->append(' - ');
        $this->view->headTitle()->append($requesttitle);


    }

    public function indexAction() {

        
        $q = trim($this->_getParam('q'));
        $type = $this->_getParam('type');
        $page = $this->_getParam('page', 1);
        $src = $this->_getParam('src');
        $opt = $this->_getParam('opt')=='1';
        $size = $this->_getParam('size');
        $form = $this->_getSearchForm();

        // filter the data from the user (xss, etc)
        $f = new Zend_Filter_StripTags ( );
        $q = $f->filter ( $q );
        $type = $f->filter ( $type );
        $src = $f->filter ( $src );
        $size = $f->filter ( $size );

        $form->getElement('q')->setValue($q);

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
       
        require_once APPLICATION_PATH.'/views/helpers/QueryString_View_Helper.php';
        $helper = new QueryString_View_Helper();
        $helper->setParams(array('q'=>$q, 'type'=>$type, 'page'=>$page, 'src'=>$src, 'opt'=>$opt, 'size' => $size));
        
        $this->view->registerHelper($helper, 'qs');


        $SphinxPaginator = new Sphinx_Paginator('idx_files',array('query'=>$q, 'src'=>$src, 'type'=>$type, 'size' => $size));

        if ($SphinxPaginator !== null) {
                //paginator
                $paginator = new Zend_Paginator($SphinxPaginator);
                 //$paginator->setDefaultScrollingStyle('Elastic');
                $paginator->setItemCountPerPage(10);
                $paginator->setCurrentPageNumber($page);

                $paginator->getCurrentItems();
                $this->view->info = array('total'=>$SphinxPaginator->tcount, 'time'=>$SphinxPaginator->time, 'q' => $q, 'start' => 1+($page-1)*10, 'end' => min($SphinxPaginator->tcount, $page*10));
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
                  . '       $("#options").toggle(false);'
                  . '       $("#show_options").text("'.$this->view->translate('Show options...').'");'
                  . '   } else {'
                  . '       $("#results").addClass("padding");'
                  . '       if (fade) $("#options").fadeIn(); else $("#options").toggle(true);'
                  . '       $("#show_options").text("'.$this->view->translate('Hide options').'");'
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

