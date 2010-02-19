<?php


class IndexController extends Zend_Controller_Action
{

    public function init()
    {

        

        
        $this->_flashMessenger = $this->_helper->getHelper ( 'FlashMessenger' );
        $this->view->mensajes = $this->_flashMessenger->getMessages ();
    }

    public function indexAction() {

        $request = $this->getRequest ();
        $form = $this->_getSearchForm();

        $lang = $request->getParam('language') ;

        var_dump($lang);
        var_dump(Zend_Registry::get('Zend_Locale'));



      


       $totalFilesIndexed = $this->fetchIndexFilesCount();
       $this->view->totalFilesIndexed = number_format($totalFilesIndexed[0]['files'], 0);


        // now check to see if the form submitted exists, and
        // if the values passed in are valid for this form
        if ($form->isValid ( $request->getPost () )) {

              // Create a filter chain and add filters
            $f = new Zend_Filter();
             $f->addFilter(new Zend_Filter_StripTags())
                    ->addFilter(new Zend_Filter_HtmlEntities());
              $q = $f->filter ( $this->_request->getPost ( 'q' ) );

              $form->setAction( $lang.'/search/'.$q);


              $form->loadDefaultDecoratorsIsDisabled(false);
              foreach($form->getElements() as $element) {
                $element->removeDecorator('DtDdWrapper');
                $element->removeDecorator('Label');

                }



        }
        // assign the form to the view
        $this->view->form = $form;
        $this->view->lang = $lang;
       
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







        public function fetchIndexFilesCount() {

                $files = new Zend_Db_Table('ff_files');
                $query = "SELECT SQL_CACHE COUNT(IdFile) as files FROM ff_file ";
                $result = $files->getAdapter()->query($query)->fetchAll();

                return $result;

            }



}