<?php
require_once APPLICATION_PATH.'/models/Files.php';
require_once APPLICATION_PATH.'/controllers/helpers/SphinxPaginator.php';
require_once APPLICATION_PATH.'/views/helpers/QueryString_View_Helper.php';
require_once APPLICATION_PATH.'/views/helpers/FileUtils_View_Helper.php';
require_once APPLICATION_PATH.'../../library/Foofind/TamingTextClient.php';

define("MAX_HITS", 2000000);
define("MAX_RESULTS", 1000);
define("PAGE_SIZE", 10);

class SearchController extends Zend_Controller_Action {

    public function init() {
         //validate domain foofind
        $this->_helper->checkdomain->check();
        
        $this->_flashMessenger = $this->_helper->getHelper ( 'FlashMessenger' );
        $this->view->mensajes = $this->_flashMessenger->getMessages ();
        $this->view->lang = $this->_helper->checklang->check();
        
        $this->config = Zend_Registry::get('config');
    }

    public function indexAction() {
        $qw = stripcslashes(strip_tags($this->_getParam('q')));
        $type = $this->_getParam('type');
        $page = $this->_getParam('page', 1);
        $src = $this->_getParam('src');
        $opt = $this->_getParam('opt');
        $size = $this->_getParam('size');
        $year = $this->_getParam('year');
        $brate = $this->_getParam('brate');

        // Create a filter chain and add filters
        $encoding = array('quotestyle' => ENT_QUOTES, 'charset' => 'UTF-8');

        $f = new Zend_Filter();
        //$f->addFilter(new Zend_Filter_HtmlEntities($encoding));
        $f->addFilter(new Zend_Filter_StringTrim());
        $f->addFilter(new Zend_Filter_StripTags($encoding));

        $q = $f->filter ( trim($qw ));
        $type = $f->filter ( $type );
        $src = $f->filter ( $src );
        $opt = $f->filter ( $opt );
        $size = $f->filter ( $size );
        $year = $f->filter ( $year );
        $brate = $f->filter ( $brate );

        if (!preg_match("/^Audio$/", $type)) $brate = null;
        if (!preg_match("/^Audio|Video$/", $type)) $year= null;

        $this->view->headTitle()->append(' - ');
        $this->view->headTitle()->append($qw);

        $form = $this->_getSearchForm();
         if (!$q) { // check if query search is empty

            $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Hey! Write something' ) );
            $this->_redirect ( '/' );
            return ;
        }


        $form->getElement('q')->setValue(trim($q));

        $form->loadDefaultDecoratorsIsDisabled(false);

        $form->addElement("hidden", "type", array("value"=>$type));
        //$form->addElement("hidden", "src", array("value"=>$src));
        $form->addElement("hidden", "opt", array("value"=>$opt));
        
        foreach($form->getElements() as $element) {
            $element->removeDecorator('DtDdWrapper');
            $element->removeDecorator('Label');
        }
                
        // assign the form to the view
        $this->view->form = $form;

        if(!$src)
        {
            if ($_COOKIE['src']) $src = $_COOKIE['src'];
        } else {
            setcookie( 'src', $src, null, '/' );
        }
        $srcs = array();
        $src2 = ($src=='')?'swftge':$src;
        $srcs['ed2k'] = (strpos($src2, 'e')===false)?$src.'e':str_replace('e', '', $src2);
        $srcs['gnutella'] = (strpos($src2, 'g')===false)?$src.'g':str_replace('g', '', $src2);
        $srcs['torrent'] = (strpos($src2, 't')===false)?$src.'t':str_replace('t', '', $src2);
        $srcs['web'] = (strpos($src2, 'w')===false)?$src.'w':str_replace('w', '', $src2);
        $srcs['ftp'] = (strpos($src2, 'f')===false)?$src.'f':str_replace('f', '', $src2);

        $conds = array('q'=>trim($q), 'src'=>$src2, 'opt'=>$opt, 'type'=>$type, 'size' => $size, 'year' => $year, 'brate' => $brate, 'page' => $page);

        $helper = new QueryString_View_Helper();
        $helper->setParams($conds);
        
        $this->view->registerHelper($helper, 'qs');
        $this->view->src = $srcs;
        $this->view->qs = $conds;
        
        $helper = new FileUtils_View_Helper();
        $helper->registerHelper($this->view);

        if ($page>MAX_RESULTS/PAGE_SIZE)
        {
            $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Foofind does not allow browse results after page 1000.' ) );
            $this->_redirect("/{$this->view->lang}/search/".$helper->qs(array(), array('page'=>1)));
            return;
        }

        $tamingServer = explode(":", $this->config->taming->server);
        $taming = new TamingTextClient($tamingServer[0], (int)$tamingServer[1]);
        $w = array("c"=>1, $this->view->lang=>200);
        if ($type) {
            foreach (Model_Files::ct2ints($type) as $cti)
                $w[Model_Files::cti2sct($cti)] = 200;
        }
        $tags = json_decode($taming->tameText(trim($q)." ", $w, 20, 4, 0.7, 0));

        $this->view->tags = array();
        $this->view->tags["count"] = count($tags);
        $mean = ($tags[0][0] + $tags[$this->view->tags["count"]-1][0])/2;
        $this->view->tags["names"] = array_map(function($res){return $res[2];}, $tags);
        $this->view->tags["weights"] = array_map(function($res) use ($mean) { return 100*max(min(1.25, $res[0]/$mean), 0.75);}, $tags);
        array_multisort($this->view->tags["names"], SORT_ASC, SORT_STRING, $this->view->tags["weights"]);
        
        // build a caching object
        if ($this->config->cache->searches) {
            // build a caching object
            $oCache = Zend_Registry::get('cache');
            $key = "srh_".$this->view->lang."_".md5("m$q s$src2 o$opt t$type s$size y$year b$brate p$page");
            $existsCache = $oCache->test($key);
        } else {
            $existsCache = false;
        }

        if  ( $existsCache  ) {
            //cache hit, load from memcache.
            $paginator = $oCache->load( $key  );
            $paginator->getAdapter()->setFileUtils($this->_helper->fileutils);
        } else {
            $SphinxPaginator = new SphinxPaginator('idx_files');
            $SphinxPaginator->setFileUtils($this->_helper->fileutils);
            $SphinxPaginator->setFilters($conds);

            $paginator = new Zend_Paginator($SphinxPaginator);
            $paginator->setDefaultScrollingStyle('Elastic');
            $paginator->setItemCountPerPage(PAGE_SIZE);
            $paginator->setCurrentPageNumber($page);
            $paginator->getCurrentItems();

            $paginator->tcount = $SphinxPaginator->tcount;
            $paginator->showImages = $SphinxPaginator->showImages;
            if (isset($SphinxPaginator->time)) $paginator->time = $SphinxPaginator->time;
            if (isset($conds['type']) && $SphinxPaginator->count()==0)
            {
                $conds['type']=null;
                $SphinxPaginator->setFilters($conds);
                $paginator->noTypeCount = $SphinxPaginator->justCount();
             } else {
                $paginator->noTypeCount = "";
             }
            
            $paginator->getAdapter()->setFileUtils(null);
            if ($this->config->cache->searches && $SphinxPaginator->canCacheResults()) $oCache->save( $paginator, $key );

            if (strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "bot")===false) {
                try {
                $urls = $SphinxPaginator->getUrls();
                if (count($urls)>0) {
                    require_once APPLICATION_PATH . '/models/Feedback.php';
                    $fbmodel = new Model_Feedback ( );
                    $fbmodel->saveVisitedLinks($urls);
                }
                } catch (Exception $e) {}
            }
        }

        $this->view->info = array('total'=>$paginator->tcount, 'time'=>$paginator->time, 'q' => $q, 'start' => 1+($page-1)*PAGE_SIZE, 'end' => min($paginator->tcount, $page*PAGE_SIZE), 'notypecount' => $paginator->noTypeCount);
        $this->view->paginator = $paginator;

        $jquery = $this->view->jQuery();
        $jquery->enable(); // enable jQuery Core Library

        $onload = '$("#show_options").click(function() '
                  . '{'
                    . 'active = $("#show_options").attr("active")=="1";'
                    . 'switchOptions(active, true);'
                  . '});'
                  . 'switchOptions('.($opt?'false':'true').', false);'
                  . 'configTaming("'.$this->view->lang.'");';

        if ($paginator->showImages)
        {
            $onload.= "$('.thumb').mouseenter(function() {"
                            ."if (thumbani!=0) clearInterval(thumbani);"
                            ."jimage = $(this);"
                            ."jimagecount = parseInt(jimage.attr('ic'));"
                            ."thumbani = setInterval(animateImage, 500);"
                        ."});"
                        ."$('.thumb').mouseleave(function() {"
                            ."if (thumbani!=0) clearInterval(thumbani);"
                        ."});"
                        ."$('.thumb').each(function() {"
                            ."icount = parseInt($(this).attr('ic'));"
                            ."src = $(this).attr('src').slice(0,-1);"
                            ."for (i=0; i<icount; i++) $('<img/>')[0].src = src+i.toString();"
                        ."});";
        }
        $function = 'function switchOptions(active, fade) {'
                    . 'if (active) {'
                        . '$("#results").removeClass("padding");'
                        . '$("#advsearch").toggle(false);'
                        . '$("#show_options").text("'.$this->view->translate('advanced search').'");'
                    . '} else {'
                    . '$("#results").addClass("padding");'
                        . 'if (fade) $("#advsearch").fadeIn(); else $("#advsearch").toggle(true);'
                        . '$("#show_options").text("'.$this->view->translate('hide advanced search').'");'
                    . '} $("#show_options").attr("active", 1-(active?1:0));'
                  . '}';
        $jquery->addJavascript($function);
        $jquery->addOnload($onload);

        $w = array("c"=>1, $this->view->lang=>200);
        if ($type) {
            foreach (Model_Files::ct2ints($type) as $cti)
                $w[Model_Files::cti2sct($cti)] = 200;
        }
        $result = json_decode($taming->tameText($q, $w, 1, 3, 0.8, 1, 0));
        if ($result && $result[0][2]!=$q) $this->view->didyoumean = $result[0][2];
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

