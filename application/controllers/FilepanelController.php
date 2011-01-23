<?php

class FilepanelController extends Zend_Controller_Action
{

    public function init()
    {
        require_once APPLICATION_PATH . '/models/Files.php';
        require_once APPLICATION_PATH . '/models/Users.php';
        require_once APPLICATION_PATH . '/views/helpers/FileUtils_View_Helper.php';

        $this->_flashMessenger = $this->_helper->getHelper ( 'FlashMessenger' );
        $this->view->mensajes = $this->_flashMessenger->getMessages ();

        $this->view->lang = $this->_helper->checklang->check();
        $this->view->langcode =  $this->_helper->checklang->getcode();

        $this->view->headScript()->appendFile( STATIC_PATH . '/js/jquery.dimensions.min.js');
        $this->view->headScript()->appendFile( STATIC_PATH . '/js/jquery.superbox.min.js');
        
        // get auth info
        $auth = Zend_Auth::getInstance ();
        $this->view->isAuth = $auth->hasIdentity ();
        if ($this->view->isAuth) $this->identity = $auth->getIdentity();

        $helper = new FileUtils_View_Helper();
        $helper->registerHelper($this->view);

        $this->config = Zend_Registry::get('config');
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
            $f->addFilter(new Zend_Filter_StripTags());
            $q = $f->filter (trim(stripcslashes(strip_tags($q))));
            $type = $f->filter ( $type );
            $src = $f->filter ( $src );
            $form->getElement('q')->setValue($q);
            $form->addElement("hidden", "type", array("value"=>$type));
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

        $this->view->form = $form;

        //lets fetch the file  ***************************************************
        $url = $this->_request->getParam ( 'uri' );
        $this->fmodel = new Model_Files();
        $this->umodel = new Model_Users();

        if (is_numeric($url) && $url<60000000)
        {
            if ($uri=$this->fmodel->getFileUrlFromID($url))
            {
                $uri = $this->_helper->fileutils->uri2url($this->_helper->fileutils->hex2uri($uri));
                $count=1;
                $newurl = str_replace("/$url", "/$uri", $_SERVER["REQUEST_URI"], $count);
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: $newurl");
                exit();
            } else {
                $this->_flashMessenger->addMessage ( $this->view->translate ( 'This link does not exist or may have been deleted!' ) );
                $this->_redirect ( '/'.$this->view->lang );
            }
        }

        $uri = $this->_helper->fileutils->url2uri($url);
        $hexuri = $this->_helper->fileutils->uri2hex($uri);

        //check if the url filename (last slash param) matches with the fetched from ddbb from this file controller
        $urlfn = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH );
        $urlfn = explode('/', $urlfn);
        $fn = null;
        if ($urlfn[4]) {
            $fn = urldecode($urlfn[4]);
            if (strlen($fn)>5 && substr($fn, -5)==".html") $fn = substr($fn, 0, -5);
        }

        // memcached results !!!!
        if ($this->config->cache->files) {
            // build a caching object
            $oCache = Zend_Registry::get('cache');
            $key = "dwd_".$this->view->lang."_".$hexuri."_".md5($fn);
            $existsCache = $oCache->test($key);
        } else {
            $existsCache = false;
        }

        if  ( $existsCache  ) {
            //cache hit, load from memcache.
            $obj = $oCache->load( $key  );
        } else {

            $obj['file'] = $this->fmodel->getFile($hexuri);

             // if the id file exists then go for the rest of data
            if ($obj['file']==null || $obj['file']['bl']!=0){
                $this->_flashMessenger->addMessage ( $this->view->translate ( 'This link does not exist or may have been deleted!' ) );
                $this->_redirect ( '/'.$this->view->lang );
            }

            $obj['file']['url'] = $url;
            $obj['file']['uri'] = $uri;

            $this->_helper->fileutils->chooseFilename($obj, $fn);
            $this->_helper->fileutils->buildSourceLinks($obj);
            $this->_helper->fileutils->chooseType($obj);
            $this->_helper->fileutils->searchRelatedFiles($obj);

            if ($this->config->cache->files) $oCache->save( $obj, $key );
        }


        $this->view->headTitle()->append(' - '.$this->view->translate( 'file info' ).' - ' );
        $this->view->headTitle()->append($obj['view']['fn']);

        //add meta to file related (better seo)
        $this->view->headMeta()->appendName('description', 'download, '.$obj['file']['x'].', '.$obj['file']['n']);
        $this->view->headMeta()->appendName('keywords',  'download, '.$obj['file']['x'].', '.$obj['file']['n']);

        $this->createComment( $hexuri );

        $obj['comments'] = $this->umodel->getFileComments( $hexuri, $this->view->lang );

        // load javascript and helpers for show comments references only if there are any comment
        if (count($obj['comments'])>0) {
            $this->view->headScript()->appendFile( STATIC_PATH . '/js/jquery.tooltip.min.js');
            $this->view->headScript()->appendFile( STATIC_PATH . '/js/jquery.bgiframe.min.js');
            $this->umodel->fillCommentsUsers($obj['comments']);

            require_once APPLICATION_PATH.'/views/helpers/Comments_View_Helper.php';
            $helper = new Comments_View_Helper();
            $this->view->registerHelper($helper, 'format_comment');

            require_once APPLICATION_PATH.'/views/helpers/TimeSpan_View_Helper.php';
            $helper = new TimeSpan_View_Helper();
            $this->view->registerHelper($helper, 'show_date_span');
        }
        
        // only get votes if file has its
        if (array_key_exists('vs', $obj['file'])) {
            $obj['votes'] = $this->umodel->getFileVotesSum($hexuri, $this->identity->_id);
            if ($myvote = $obj['votes']['user']) {
                if ($myvote>0)
                    $this->view->myvote = "upactive";
                else if ($myvote<0)
                    $this->view->myvote = "downactive";
            }
        } else {
            $obj['votes'] = null;
        }

        if ($this->isAuth) {
            $obj['cvotes'] = $this->umodel->getUserFileVotes($hexuri, $this->identity->_id);
            foreach ($obj['cvotes'] as $comment=>$vote)
            {
                if ($vote['k']>0)
                    $obj['comments'][$comment]['myvote'] = "upactive";
                else if ($vote['k']<0)
                    $obj['comments'][$comment]['myvote'] = "downactive";
            }
        }
        $this->view->file = $obj;

        // get current jQuery handler based on noConflict settings
        $jqHandler = ZendX_JQuery_View_Helper_JQuery::getJQueryHandler();

        $paginator = Zend_Paginator::factory($obj['comments']);
        $paginator->setItemCountPerPage(25);
        $paginator->setCurrentPageNumber($this->_getParam('page'));
        Zend_Paginator::setDefaultScrollingStyle('Sliding');
        $paginator->setView($this->view);

        //this is paginator
        $this->view->paginator = $paginator;

        //save non-bot accesses for shake
        /*if (strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "bot")===false)
        {
            $ip = $_SERVER["SERVER_ADDR"];
            $this->fmodel->shakeFile($hexuri, $ip);
        }*/
    }

    public function createComment($hexuri) {

        $request = $this->getRequest();
        $form = $this->_getCommentForm();
        if (!$request->isPost() || !$form) return;

        //if userType = 1 dont let vote
       if ( $this->identity->type == 1 ){
           echo 'You are not allowed to do that. (user type 1)';
           return ;
       }

        // now check to see if the form submitted exists, and
        // if the values passed in are valid for this form
        if (!$form->isValid($request->getPost())) return;

        $formulario = $form->getValues();
        $formulario['_id'] = $this->identity->_id.'_'.microtime(true);
        $formulario['f'] = new MongoId($hexuri);
        $formulario['l'] = $this->view->lang;
        $formulario['d'] = new MongoDate(time());
        $formulario['k'] = $this->identity->karma;
        
        $this->umodel->saveComment( $formulario );

        $this->fmodel->updateComments($hexuri, $this->umodel->getFileCommentsSum( $hexuri ) );

        $this->_flashMessenger->addMessage ( $this->view->translate ( 'Comment published succesfully!' ) );
        $this->_redirect($_SERVER['REQUEST_URI']);
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
        if ($this->view->isAuth) {

            require_once APPLICATION_PATH . '/forms/Comment.php';
            $form = new Form_Comment();
            if ($this->getRequest ()->isPost () ) $form->populate($this->getRequest()->getPost());

            $this->view->createcomment = $form;
            return $form;

        } else {
            $this->view->createcomment ="<a style='float:left' href='/{$this->view->lang}/auth/login' rel='superbox[ajax][/{$this->view->lang}/auth/login/source/comment.foo]'>".$this->view->translate('Add a comment')."</a>";
        }
    }
}
