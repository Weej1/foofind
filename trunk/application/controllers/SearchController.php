<?php

class SearchController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }




    public function indexAction() {

        $request = $this->getRequest ();
        $q = $this->_getParam('q');
        $form = $this->_getSearchForm();

        
        //$listToolsForm->setMethod('post');

        // collect the data from the user
        $f = new Zend_Filter_StripTags ( );
        $q = $f->filter ( $q );

        $form->getElement('q')->setValue($q); 
       
        //Zend_Debug::dump($q);
        //Zend_Debug::dump($form);


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
         echo "WARNING: " . $cl->GetLastWarning() . " ";
         }

     $this->view->list = $result;
  }






                // fetch alllllll from model
               // $this->view->list = $this->_listAction();


                //paginator
//                $page = $this->_getParam('page');
//                $paginator = Zend_Paginator::factory($this->view->list);
//                $paginator->setDefaultScrollingStyle('Elastic');
//                $paginator->setItemCountPerPage(10);
//                $paginator->setCurrentPageNumber($page);
//
//                $this->view->paginator=$paginator;




    }




        protected  function _listAction() {
                $searchModel = new Model_Search();
                return $searchModel->fetchFiles();
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

