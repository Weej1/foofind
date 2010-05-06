<?php

require_once APPLICATION_PATH . '/models/Files.php';

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
        $this->_flashMessenger = $this->_helper->getHelper ( 'FlashMessenger' );
        $this->view->mensajes = $this->_flashMessenger->getMessages ();
    }

    public function indexAction()
    {

        $this->view->lang =  $this->_helper->checklang->check();
        $this->view->totalFilesIndexed = Zend_Locale_Format::toNumber($this->fetchQuery(new ff_file(), "SELECT COUNT(IdFile) as res FROM ff_file"),
                                        array( 'locale' => $this->view->lang));
        
        $request = $this->getRequest ();
        $form = $this->_getSearchForm();
        
        if ($_COOKIE['src']) {
            $form->addElement('hidden', 'src', array('value'=>$_COOKIE['src']));
        }

        $form->setAction( '/'. $this->view->lang.'/search/');
        $form->loadDefaultDecoratorsIsDisabled(false);
        foreach($form->getElements() as $element) {
            $element->removeDecorator('DtDdWrapper');
            $element->removeDecorator('Label');
        }
        // assign the form to the view
        $this->view->form = $form;
        
    }


    public function kkAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout()->disableLayout();

        $urlrequest = 'http://foofind.com/api/?method=getSearch&q=centos&lang=es&src=wftge&opt=&type=&size=&year=&brate=&page=';

           $xml = file_get_contents( $urlrequest );

          $doc = new DOMDocument();
          $doc->load( $urlrequest );

           
           $items = $doc->getElementsByTagName( "item" );
          
                  foreach( $items as $item )
                  {
                  $dlinks = $item->getElementsByTagName( "dlink" );
                  $dlink = $dlinks->item(0)->nodeValue;

                  var_dump($item);

                  echo "$dlink \n";
                  }
    }

    public function queryAction()
    {
        $type =  $this->getRequest()->getParam('type') ;
        $this->_helper->layout->disableLayout();
        switch ($type)
        {
            case 'count':
                $table = new ff_file();
                $query = "SELECT COUNT(IdFile) as res FROM ff_file";
                break;
            case 'ts':
                $table = new ff_touched();
                $query = "SELECT MAX(timestamp) as res FROM ff_touched";
                break;
        }
        
        if ($table) $this->view->value = $this->fetchQuery($table, $query);
    }

    function fetchQuery($table, $query)
    {
         $row = $table->getAdapter()->query($query)->fetchAll();
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