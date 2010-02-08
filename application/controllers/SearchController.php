<?php

class SearchController extends Zend_Controller_Action {

    public function init() {
        /* Initialize action controller here */
    }




    public function indexAction() {

        $request = $this->getRequest ();
        $q = $this->_getParam('q');
        $form = $this->_getSearchForm();

        

        // filter the data from the user (xss, etc)
        $f = new Zend_Filter_StripTags ( );
        $q = $f->filter ( $q );

        $form->getElement('q')->setValue($q); 
       
        
        // assign the form to the view
        $this->view->form = $form;



        require_once ( APPLICATION_PATH . '../../library/Sphinx/sphinxapi.php' );

        $cl = new SphinxClient();
        $cl->SetServer( "trasiego", 3312 );
        $cl->SetMatchMode( SPH_MATCH_ANY  );

        //$this->view->list = $cl;

         $result = $cl->Query( $q, 'idx_files' );
       


        if ( $result === false ) {
      echo "Query failed: " . $cl->GetLastError() . ".\n";
  }
  else {
      if ( $cl->GetLastWarning() ) {
          echo "WARNING: " . $cl->GetLastWarning() . "";
      }

      if ( ! empty($result["matches"]) ) {
          foreach ( $result["matches"] as $doc => $docinfo ) {
               //var_dump($this->_listFilenames($doc)->toArray());
               //var_dump($this->_listSources($doc)->toArray());
               
              $this->chufa[$doc]['filenames'] = $this->_listFilenames($doc)->toArray();
              $this->chufa[$doc]['sources'] = $this->_listSources($doc)->toArray();
              //var_dump($this->chufa[$doc]);

          }

        
      }
  
     // var_dump($result[total_found]);
     $this->view->counts = $result[total_found];
     $this->view->list = $this->chufa;

  }






                // fetch alllllll from model
                //$this->view->list = $this->_listAction($id);


                  //paginator
                $page = $this->_getParam('page');
                $paginator = Zend_Paginator::factory($this->view->list);
                $paginator->setDefaultScrollingStyle('Elastic');
                $paginator->setItemCountPerPage(10);
                $paginator->setCurrentPageNumber($page);

                $this->view->paginator=$paginator;



    }




        protected  function _listFilenames($id) {
                $searchModel = new Model_Search();
                return $searchModel->fetchFilenames($id);
}


        protected function _listSources($id){
                $searchModel = new Model_Search();
                return $searchModel->fetchSources($id);
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

