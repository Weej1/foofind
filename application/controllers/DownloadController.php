<?php

require_once ( APPLICATION_PATH . '/models/ContentType.php' );
require_once APPLICATION_PATH . '/models/Files.php';
require_once APPLICATION_PATH . '/models/Users.php';

class DownloadController extends Zend_Controller_Action
{

    public function init()
    {
        $this->_flashMessenger = $this->_helper->getHelper ( 'FlashMessenger' );
        $this->view->mensajes = $this->_flashMessenger->getMessages ();

        $this->view->lang =  $this->_helper->checklang->check();
    }

    public function fileAction()
    {

        $request = $this->getRequest ();
        $form = $this->_getSearchForm();


        //plugin Qs
        require_once APPLICATION_PATH.'/views/helpers/QueryString_View_Helper.php';

        if ($_SERVER['HTTP_REFERER'])
        {
            parse_str(substr(strstr($_SERVER['HTTP_REFERER'], '?'), 1));

            $f = new Zend_Filter();
            $f->addFilter(new Zend_Filter_StringTrim());
            $f->addFilter(new Zend_Filter_StripTags($encoding));
            $q = $f->filter (trim(stripcslashes(strip_tags($q))));
            $type = $f->filter ( $type );
            $src = $f->filter ( $src );
            $form->getElement('q')->setValue($q);
            $form->addElement("hidden", "type", array("value"=>$type));
            $form->addElement("hidden", "src", array("value"=>$src));
        }

        if(!$src) if ($_COOKIE['src']) $src = $_COOKIE['src'];

        $src2 = ($src=='')?'wftge':$src;
        $conds = array('q'=>trim($q), 'src'=>$src2, 'opt'=>$opt, 'type'=>$type, 'size' => $size, 'year' => $year, 'brate' => $brate, 'page' => $page);

        $helper = new QueryString_View_Helper();
        $helper->setParams($conds);

        $this->view->registerHelper($helper, 'qs');
        $this->view->src = $srcs;
        $this->view->qs = $conds;
    

        // now check to see if the form submitted exists, and
        // if the values passed in are valid for this form
        $form->loadDefaultDecoratorsIsDisabled(false);
        $form->setAction( '/'. $this->view->lang.'/search/');
        foreach($form->getElements() as $element) {
            $element->removeDecorator('DtDdWrapper');
            $element->removeDecorator('Label');
        }

        // assign the form to the view
        $this->view->form = $form;

        //*************************************************************************** get file
        $id = $this->_request->getParam ( 'id' );
        $fmodel = new Model_Files();
        $this->view->file = $fmodel->getFile( $id );

        // if the id file exists then go for the rest of data
        if (!$this->view->file){
            $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'This link does not exist or may have been deleted!' ) );
            $this->_redirect ( '/'.$this->view->lang );
        }

        //check if the url filename (last slash param) matches with the fetched from ddbb from  this file controller
        $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH );
        $url = explode('/', $url);

        $fn = NULL;
        if ($url[4] ) {
            $fn = $url[4];
            if (strlen($fn)>5 && substr($fn, -5)==".html") $fn = substr($fn, 0, -5);
        }
        $this->view->filename = $fmodel->getFileFilename( $id, $fn);
        $idfn = $this->view->filename['IdFilename'];
        $this->view->metadata = $fmodel->getMetadata( "IdFile = $id" );
        $this->view->sources = $fmodel->getSources( "IdFile = $id" );

        $this->view->file_size = $this->_formatSize($this->view->file['Size']);
        $this->view->headTitle()->append(' - '.$this->view->translate( 'download' ).' - ' );

        $this->view->headTitle()->append($this->view->filename['Filename']);

        $this->umodel = new Model_Users();
        $this->view->votes = array('neg'=> 0, 'pos'=> 0);
        
        $votes = $this->umodel->getVotes( $id );
        foreach ($votes as $v)
        {
            switch ($v['VoteType'])
            {
                case 1:
                    $this->view->votes['pos'] = $v['c'];
                    break;
                case 2:
                    $this->view->votes['neg'] = -$v['c'];
                    break;
            }
        }

        $this->createComment($id, $idfn);
        $this->view->comments = $this->umodel->getComments( $id );
        
    }

    public function createComment($id, $idfn) {
        $request = $this->getRequest();
        $form = $this->_getCommentForm();
        if (!$request->isPost() || !$form) return;

        //first we check if user is logged, if not redir to login
        $auth = Zend_Auth::getInstance ();
        
        // now check to see if the form submitted exists, and
        // if the values passed in are valid for this form
        if (!$form->isValid($request->getPost())) return;

        //anti hoygan to body
        $formulario = $form->getValues();
        $split=explode(". ", $formulario['text']);

        foreach ($split as $sentence) {
                $sentencegood = ucfirst(mb_convert_case($sentence, MB_CASE_LOWER, "UTF-8"));
                $formulario['text'] = str_replace($sentence, $sentencegood, $formulario['text']);
        }

        //strip html tags to body
        $formulario['text'] = strip_tags($formulario['text']);
        $formulario['IdFilename'] = $idfn;
        $formulario['IdFile'] = $id;
        $formulario['IdUser'] = $auth->getIdentity()->IdUser;
        $formulario['lang'] = $this->view->lang;
        $this->umodel->saveComment( $formulario );
        $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Comment published succesfully!' ) );
        $form->reset();
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

    /**
     *
     * @return Form_AdEdit
     */
    protected function _getCommentForm() {
        //if user logged in, show the comment form, if not show the login link
        $auth = Zend_Auth::getInstance ();
        $this->view->isAuth = $auth->hasIdentity ();
        if ($this->view->isAuth) {
            require_once APPLICATION_PATH . '/forms/Comment.php';
            $form = new Form_Comment();
            if ($this->getRequest ()->isPost () ) $form->populate($this->getRequest()->getPost());
            $this->view->createcomment = $form;
            return $form;
        } else {
            $this->view->createcomment ='<a href="/' . $this->view->lang . '/auth/login">' . $this->view->translate ( 'login to post a comment' ) . '</a> ';
        }
    }


    //TODO refactor this function ,is duplicated from search controller (this sucks)
    protected function _formatSize($bytes)
    {
        $size = $bytes / 1024;
        if($size < 1024)
        {
            $size = number_format($size, 2);
            $size .= '&nbsp;KB';
        }
        else
        {
            if ($size / 1024 < 1024)
            {
                $size = number_format($size / 1024, 2);
                $size .= '&nbsp;MB';
            }
            else if ($size / 1024 / 1024 < 1024)
            {
                $size = number_format($size / 1024 / 1024, 2);
                $size .= '&nbsp;GB';
            }
        }
        return $size;
    }





}