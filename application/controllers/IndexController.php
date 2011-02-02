<?php


class IndexController extends Zend_Controller_Action
{
    public function init()
    {
        // validate domain foofind
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
            $data = (array)$auth->getIdentity();
            $data['lang'] = $lang;
            $umodel->updateUser($data['username'], $data);
            $auth->getStorage()->write((object)$data);
            unset($umodel);
        }

        setcookie ( "lang", $lang, null, '/' );

        if ($this->hasValidReferer())
        {
            $new_url = explode("/", $this->referer);
            if (count($new_url)>3 && strlen($new_url[3])>0) $new_url[3] = $lang;
            $this->_redirect(join("/",$new_url));
        }
        else
            $this->_redirect ( '/' );
    }

    public function indexAction()
    {
        $f = new Zend_Filter();
        //$f->addFilter(new Zend_Filter_HtmlEntities($encoding));
        $f->addFilter(new Zend_Filter_StringTrim());
        $f->addFilter(new Zend_Filter_StripTags());

        $type = $this->_getParam('type');
        $type = $f->filter ( $type );
        $this->view->qs = array('type'=>$type);

        $request = $this->getRequest ();
        $form = $this->_getSearchForm();
        
        $form->addElement('radio', 'src', array(
           
            'label'      => 'source:',
            'required'   => true,
            'order'         => 2,
            'multioptions'   => array(
                            'wftge' => 'All',
                            'wf' => 'Direct downloads',
                            't' => 'Torrents',
                            's' => 'Streaming',
                            'g' => 'Gnutella',
                            'e' => 'Ed2k'
                            ),
            'separator'     => '',
            'value'         =>($_COOKIE['src'] ) ? $_COOKIE['src'] : 'wftge'             
        ));
        $form->addElement('hidden', 'type', array('value'=>$type));
        $form->setAction( '/'. $this->view->lang.'/search/');
        foreach($form->getElements() as $element) {
            $element->removeDecorator('DtDdWrapper');
            $element->removeDecorator('Label');
        }

        $jquery = $this->view->jQuery();
        $jquery->enable(); // enable jQuery Core Library

        // get current jQuery handler based on noConflict settings
        $jqHandler = ZendX_JQuery_View_Helper_JQuery::getJQueryHandler();
        
        $onload = '(".tabs a").click(function(event) '
                  . '{'
                  . '   event.preventDefault();'
                  . '   $(".tabs a").removeClass("actual");'
                  . '   $(this).addClass("actual"); '
                  . '   var v=$(this).attr("href");'
                  . '   v=v.substring(Math.abs(v.indexOf("="))+1);'
                  . '   $("#type").val(v);'
                  . '});';

        $jquery->addOnload($jqHandler . $onload);

        // assign the form to the view
        $this->view->form = $form;

        if (strpos($_SERVER['HTTP_USER_AGENT'], "MSIE")!==FALSE) $this->view->extra .= " iehome";
    }

    public function queryAction()
    {
        $this->_helper->layout->disableLayout();
        $model = new Model_Files();
        $this->view->value = $model->countFiles();
        unset($model);
    }


    public function lastfilesAction()
    {
        $this->view->headTitle()->append(' - ');
        $this->view->headTitle()->append($this->view->translate('Last indexed files'));
        $this->_helper->layout()->setLayout('with_search_form');
        
        $this->view->lang =  $this->_helper->checklang->check();


        $limit = 100;

        $fmodel = new Model_Files();

        $paginator = Zend_Paginator::factory( $fmodel->getLastFilesIndexed( (int) $limit ));
        $paginator->setItemCountPerPage(25);
        $paginator->setCurrentPageNumber($this->_getParam('page'));
        Zend_Paginator::setDefaultScrollingStyle('Sliding');

        $this->view->lastfiles = $paginator;

        unset($fmodel);
    }


    public function sitemapAction()
    {
        $this->view->headTitle()->append(' - ');
        $this->view->headTitle()->append($this->view->translate('Last indexed files'));
        $this->_helper->layout->disableLayout();

        $this->view->lang =  $this->_helper->checklang->check();

        $limit = 200;

        $fmodel = new Model_Files();
        $this->view->lastfiles = $fmodel->getLastFilesIndexed( (int) $limit );

        unset($fmodel);
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