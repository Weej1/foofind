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
        $this->cl->SetGroupBy( "idfile", SPH_GROUPBY_ATTR, "isources DESC, fileWeight DESC, ");
        $this->cl->SetMaxQueryTime(200);
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

        $result = $this->cl->Query( $this->conditions['query'], $this->table );
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
                                $docs[$id]['filename'] = $row['Filename'];
                            }
                        }

                        // get sources for files
                        $sources = new Zend_Db_Table('ff_sources');
                        foreach ($sources->fetchAll("IdFile in ($ids)") as $row)
                        {
                               /* * 1->gnutella
    * 2->ed2k
    * 3->bittorrent
    * 4->http link Jamendo
    * 5->Tiger Hash
    * 6->MD5 Hash
    * 7->BTH Hash*/
                            // TODO: choose better source for each file
                            // TODO: create e-link
                            $id = $row['IdFile'];
                            switch ($row['Type'])
                            {
                                case 1:
                                    $link = "magnet:?dt=".$docs[$id]['filename']."&xl=".$docs[$id]['attrs']['size']."&xt=urn:sha1:".$row['Uri'];
                                    break;
                                case 2:
                                    $link = "ed2k://|file|".$docs[$id]['filename']."|".$docs[$id]['attrs']['size']."|".$row['Uri'];
                                    break;
                                case 3:
                                    $link = $row['Uri'];
                                    break;
                            }
                            $docs[$row['IdFile']]['link'] = $link;
                        }

                        // get metadata for files
                        $metadata = new Zend_Db_Table('ff_metadata');
                        foreach ($metadata->fetchAll("IdFile in ($ids)") as $row)
                            $md[$row['IdFile']][$row['KeyMD']]=$row['ValueMD'];

                        // choose better type for each file and get description for file
                        foreach ($docs as $id => $doc)
                        {
                            if ($doc['type']==null && count($doc['type_prop'])>0)
                            {
                                // TODO: count each option and choose better
                                $docs[$id]['type'] = $doc['type_prop'][0];
                            }

                            if ($doc['attrs']['size']>0) $docs[$id]['size'] = formatSize($doc['attrs']['size']);
                            $docs[$id]['sources'] = $doc['attrs']['isources'];
                            try { 
                                $func = 'format'.$docs[$id]['type'];
                                if ($func) $docs[$id]['info'] = $func($md[$id]);}
                            catch (Exception $ex) {}

                        }

                        return $docs;
                }
        }
        return null;
    }

    public function count()
    {
        return $this->tcount;
    }
}

class SearchController extends Zend_Controller_Action {

    public function init() {
        /* Initialize action controller here */
    }

    public function indexAction() {

        $request = $this->getRequest ();
        $q = $this->_getParam('q');
        $type = $this->_getParam('type');
        $page = $this->_getParam('page', 1);
        $form = $this->_getSearchForm();

        // filter the data from the user (xss, etc)
        $f = new Zend_Filter_StripTags ( );
        $q = $f->filter ( $q );
        $type = $f->filter ( $type );

        $form->getElement('q')->setValue($q);

        $form->loadDefaultDecoratorsIsDisabled(false);
        foreach($form->getElements() as $element) {
            $element->removeDecorator('DtDdWrapper');
            $element->removeDecorator('Label');
        }
                
        // assign the form to the view
        $this->view->form = $form;
        $this->view->q = $q;

        $SphinxPaginator = new Sphinx_Paginator('idx_files',array('query'=>$q, 'type'=>$type));

        if ($SphinxPaginator !== null) {
                //paginator
                $paginator = new Zend_Paginator($SphinxPaginator);
                 //$paginator->setDefaultScrollingStyle('Elastic');
                $paginator->setItemCountPerPage(10);
                $paginator->setCurrentPageNumber($page);

                $paginator->getCurrentItems();
                $this->view->info = array('total'=>ceil($SphinxPaginator->tcount/1000)*1000, 'time'=>$SphinxPaginator->time, 'q' => $q, 'start' => 1+($page-1)*10, 'end' => $page*10);

                $this->view->paginator=$paginator;
        }
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

