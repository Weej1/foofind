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

       // var_dump($lang);
              
       $this->view->totalFilesIndexed = $this->fetchQuery("ff_file", "SELECT COUNT(IdFile) as res FROM ff_file");

        // now check to see if the form submitted exists, and
        // if the values passed in are valid for this form
        if ($form->isValid ( $request->getPost () )) {

              // Create a filter chain and add filters
             $f = new Zend_Filter();
             $f->addFilter(new Zend_Filter_StripTags())
                    ->addFilter(new Zend_Filter_HtmlEntities());
              $q = $f->filter ( $this->_request->getPost ( 'q' ) );

              $form->setAction( '/'.$lang.'/search/'.$q);

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

    public function queryAction()
    {
        $type =  $this->getRequest()->getParam('type') ;
        $this->_helper->layout->disableLayout();
        switch ($type)
        {
            case 'count':
                $table = "ff_file";
                $query = "SELECT COUNT(IdFile) as res FROM ff_file";
                break;
            case 'ts':
                $table = "ff_touched";
                $query = "SELECT MAX(timestamp) as res FROM ff_touched";
                break;
        }
        
        if ($table) $this->view->value = $this->fetchQuery($table, $query);
    }

    function fetchQuery($table, $query)
    {
         $t = new Zend_Db_Table($table);
         $row = $t->getAdapter()->query($query)->fetchAll();
         return $row[0]['res'];
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