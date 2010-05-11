<?php

require_once APPLICATION_PATH . '/models/Files.php';

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
        $this->_flashMessenger = $this->_helper->getHelper ( 'FlashMessenger' );
        $this->view->mensajes = $this->_flashMessenger->getMessages ();
        $this->view->lang =  $this->_helper->checklang->check();
    }

    public function setlangAction()
    {
        $this->referer = $_SERVER['HTTP_REFERER'];
        $lang = $this->_getParam("language");
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity())
        {
            $umodel = new Model_Users();
            $umodel->updateUser(array('IdUser'=>$auth->getIdentity()->IdUser,'lang'=>$lang));
        }

        setcookie ( "lang", $lang, null, '/' );

        if ($this->hasValidReferer())
        {
            $new_url = explode("/", $this->referer);
            if (count($new_url)>2) $new_url[3] = $lang;
            $this->_redirect(join("/",$new_url));
        }
        else
            $this->_redirect ( '/' );
    }

    public function indexAction()
    {

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
    
    function hasValidReferer()
    {
        if (!$this->referer) return false;

        # invalid if is the same URL
        $currentURI = $_SERVER['SCRIPT_URI'];
        if (strcmp($this->referer, $currentURI) == 0) return false;

        # invalid if is not in this site
        $barpos = strpos($currentURI, "/", 8);
        if (strncmp($this->referer, $currentURI, $barpos ) != 0) return false;

        return true;
    }
}