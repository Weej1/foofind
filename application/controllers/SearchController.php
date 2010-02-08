<?php

class Sphinx_Paginator implements Zend_Paginator_Adapter_Interface {
	public function __construct($table, $conditions = array())
	{
		if(!is_array($conditions))
			$conditions = array( $conditions );
 
		$this->conditions = $conditions;
 
		$this->table	  = $table;

             

                $sphinxConf =  new Zend_Config_Ini( APPLICATION_PATH . '/configs/application.ini' , 'production'  );
                $sphinxServer = $sphinxConf->sphinx->server;


        	$this->cl = new SphinxClient();
        	$this->cl->SetServer( $sphinxServer, 3312 );
        	$this->cl->SetMatchMode( SPH_MATCH_ANY  );
		$this->cl->SetRankingMode( SPH_RANK_PROXIMITY_BM25 );
		$this->cl->SetSortMode( SPH_SORT_EXTENDED, "isources DESC" );
		$this->cl->SetGroupBy( "idfile", SPH_GROUPBY_ATTR, "@weight DESC, isources DESC");
		$this->count = 0;
	}
 
	public function getItems($offset, $itemCountPerPage)
	{
		$this->cl->SetLimits( $offset, $itemCountPerPage);
	        $result = $this->cl->Query( $this->conditions[0], $this->table );
		if ( $result === false ) {
      			echo "Query failed: " . $this->cl->GetLastError() . ".\n";
 		} else {
			if ( $this->cl->GetLastWarning() ) {
		          echo "WARNING: " . $this->cl->GetLastWarning() . "";
		      }

	      		if ( ! empty($result["matches"]) ) {
        			foreach ( $result["matches"] as $doc => $docinfo )
				{ 
					$id = $docinfo["attrs"]["idfile"];
        	    			$docs[$id]['filenames'] = $this->_listFilenames($id)->toArray();
              				$docs[$id]['sources'] = $this->_listSources($id)->toArray();
              				$docs[$id]['metadata'] = $this->_listMetadata($id)->toArray();
				}
				return $docs;
			}
		}
		return null;
		
      	}
  
	public function count()
	{
		$this->cl->SetLimits(0, 1);
                $result = $this->cl->Query( $this->conditions[0], $this->table );
		return $result["total_found"];
	}



        protected  function _listFilenames($id) {
                $searchModel = new Model_Search();
                return $searchModel->fetchFilenames($id);
}

        protected  function _listMetadata($id) {
                $searchModel = new Model_Search();
                return $searchModel->fetchMetadata($id);
}

        protected function _listSources($id){
                $searchModel = new Model_Search();
                return $searchModel->fetchSources($id);
        }


}

class SearchController extends Zend_Controller_Action {

    public function init() {
        /* Initialize action controller here */
    }




    public function indexAction() {

        $request = $this->getRequest ();
        $q = $this->_getParam('q');
        $form = $this->_getSearchForm();

        $page = $this->_getParam('page');
        

        // filter the data from the user (xss, etc)
        $f = new Zend_Filter_StripTags ( );
        $q = $f->filter ( $q );

        $form->getElement('q')->setValue($q); 
       
        
        // assign the form to the view
        $this->view->form = $form;



        require_once ( APPLICATION_PATH . '../../library/Sphinx/sphinxapi.php' );



 // }






                // fetch alllllll from model
                //$this->view->list = $this->_listAction($id);


                  //paginator
                $paginator = new Zend_Paginator(new Sphinx_Paginator('idx_files',$q));  //::factory($this->view->list);
                //$paginator->setDefaultScrollingStyle('Elastic');
                $paginator->setItemCountPerPage(10);
                $paginator->setCurrentPageNumber($page);

                $this->view->paginator=$paginator;



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

