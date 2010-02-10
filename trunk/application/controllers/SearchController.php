<?php


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
        $this->cl->SetMatchMode( SPH_MATCH_ALL  );
        $this->cl->SetRankingMode( SPH_RANK_PROXIMITY_BM25 );
        $this->cl->SetSortMode( SPH_SORT_EXTENDED, "isources DESC" );
        $this->cl->SetGroupBy( "idfile", SPH_GROUPBY_ATTR, "@weight DESC, isources DESC");
        $this->cl->SetMaxQueryTime(200);
        $this->tcount = 0;
    }
    
    public function getItems($offset, $itemCountPerPage)
    {
        $this->cl->SetLimits( $offset, $itemCountPerPage);
        $type = $this->conditions['type'];
        if ($type) {
            $this->cl->SetFilter('crcextension', $type);
        }
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
                        $ids=$idsfn='';
                        foreach ( $result["matches"] as $doc => $docinfo )
                        {
                            
                                $id = $docinfo["attrs"]["idfile"];
                                $ids .= ",$id";
                                $idsfn .= ",$doc";
                                $docs[$id]['metadata'] = array();
                                $docs[$id]['sources'] = array();
                        }

                        $ids = substr($ids, 1);
                        $idsfn = substr($idsfn, 1);

                        $filenames = new Zend_Db_Table('ff_filename');
                        foreach ($filenames->fetchAll("IdFilename in ($idsfn)") as $row)
                                $docs[$row['IdFile']]['filename'] = $row;

                        $sources = new Zend_Db_Table('ff_sources');
                        foreach ($sources->fetchAll("IdFile in ($ids)") as $row)
                                $docs[$row['IdFile']]['sources'] []=$row;

                        $metadata = new Zend_Db_Table('ff_metadata');
                        foreach ($metadata->fetchAll("IdFile in ($ids)") as $row)
                                $docs[$row['IdFile']]['metadata'][$row['KeyMD']]=$row;

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
        global $content;

        $request = $this->getRequest ();
        $q = $this->_getParam('q');
        $type = $this->_getParam('type');
        $page = $this->_getParam('page');
        $form = $this->_getSearchForm();


        // filter the data from the user (xss, etc)
        $f = new Zend_Filter_StripTags ( );
        $q = $f->filter ( $q );
        $type = $f->filter ( $type );

        $form->getElement('q')->setValue($q);
        //$form->getElement('type')->setValue($type);

        if ($type!=null)
        {
            $temp = $content['types'][$type];
            if ($temp) {
                $type = $temp['crcExt'];
            } else {
                $type = null;
            }
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
                $this->view->info = array('total'=>$SphinxPaginator->tcount, 'time'=>$SphinxPaginator->time);
                $this->view->content = $content;
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

