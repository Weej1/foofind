<?php

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction() {


       

        $request = $this->getRequest ();
        $form = $this->_getSearchForm();
        

                
                if ($this->getRequest ()->isGet ()) {

                        // now check to see if the form submitted exists, and
                        // if the values passed in are valid for this form
                        if ($form->isValid ( $request->getPost () )) {

                              // filter the input
                              $f = new Zend_Filter_StripTags ( );
                              $q = $f->filter ( $this->_request->getPost ( 'q' ) );

                              $form->setAction('/search/'.$q);


                        }
                }
                // assign the form to the view
                $this->view->form = $form;


              

    }





    /**
         *
         * @return Form_Contact
         */
        protected function _getSearchForm() {
                require_once APPLICATION_PATH . '/forms/Search.php';
                $form = new Form_Search( );
                
                return $form;
        }




}

