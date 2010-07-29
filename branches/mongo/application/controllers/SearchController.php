<?php
require_once APPLICATION_PATH . '/models/Files.php';

define(MAX_RESULTS, 1000);

class Sphinx_Paginator implements Zend_Paginator_Adapter_Interface {
    public function __construct($table, $fileutils)
    {
        $this->table      = $table;
        $this->fileutils  = $fileutils;

        require_once ( APPLICATION_PATH . '/models/Files.php' );
        require_once ( APPLICATION_PATH . '../../library/Sphinx/sphinxapi.php' );
        $sphinxConf = new Zend_Config_Ini( APPLICATION_PATH . '/configs/application.ini' , 'production'  );
        $sphinxServer = $sphinxConf->sphinx->server;

        $this->tcount = 0;

        $this->cl = new SphinxClient();
        $this->cl->SetServer( $sphinxServer, 3312 );
        $this->cl->SetMatchMode( SPH_MATCH_EXTENDED2 );
        $this->cl->SetRankingMode( SPH_RANK_FOOGENERIC );
        //$this->cl->SetFieldWeights(array('metadata' => 1, 'filename' => 10));
        $this->cl->SetSelect("*, @weight as sw, sum(@weight*w) as fw");
        $this->cl->SetSortMode( SPH_SORT_EXTENDED, "fw DESC" );
        //$this->cl->SetMaxQueryTime(1000);
    }
    public function setFilters($conditions)
    {
        global $content;

        if(!is_array($conditions) AND !empty($conditions))
            $conditions = array( $conditions );

        $this->cl->ResetFilters();
        $this->cl->SetFilter('bl', array(0));

        $this->type = $conditions['type'];
        if ($this->type)
        {
            $types = Model_Files::ct2ints($this->type);
            if ($types) $this->cl->SetFilter('ct', $types);
        }

        $this->src = $conditions['src'];
        if ($this->src)
        {
            $this->srcs = array();
            foreach (str_split("swftge") as $s)
            {
                if (strstr($this->src, $s)) $this->srcs = array_merge($this->srcs, Model_Files::src2ints($s));
            }
            if (count($this->srcs)>0) $this->cl->SetFilter('t', $this->srcs);
        }

        $this->size = $conditions['size'];
        if ($this->size)
        {
            switch ($this->size)
            {
                case 1:
                    $this->cl->SetFilterRange('z', 1, 1048576);
                    break;
                case 2:
                    $this->cl->SetFilterRange('z', 1, 10485760);
                    break;
                case 3:
                    $this->cl->SetFilterRange('z', 1, 104857600);
                    break;
                case 4:
                    $this->cl->SetFilterRange('z', 0, 104857600, true);
                    break;
            }
        }

        $this->brate = $conditions['brate'];
        if ($this->brate)
        {
            switch ($this->brate)
            {
                case 1:
                    $this->cl->SetFilterRange('mab', 0, 127, true);
                    break;
                case 2:
                    $this->cl->SetFilterRange('mab', 0, 191, true);
                    break;
                case 3:
                    $this->cl->SetFilterRange('mab', 0, 255, true);
                    break;
                case 4:
                    $this->cl->SetFilterRange('mab', 0, 319, true);
                    break;
            }
        }

        $this->year = $conditions['year'];
        if ($this->year)
        {
            switch ($this->year)
            {
                case 1:
                    $this->cl->SetFilterRange('may', 0, 59);
                    break;
                case 2:
                    $this->cl->SetFilterRange('may', 60, 69);
                    break;
                case 3:
                    $this->cl->SetFilterRange('may', 70, 79);
                    break;
                case 4:
                    $this->cl->SetFilterRange('may', 80, 89);
                    break;
                case 5:
                    $this->cl->SetFilterRange('may', 90, 99);
                    break;
                case 6:
                    $this->cl->SetFilterRange('may', 100, 109);
                    break;
                case 7:
                    $nowy = (int)date('Y');
                    $this->cl->SetFilterRange('may', $nowy-1, $nowy);
                    break;
            }
        }
        $this->query = preg_replace("/[\W_]-[\W_]/iu", " ", $conditions['q']);
    }

    public function justCount()
    {
        $start_time = microtime(true);
        $this->cl->SetLimits( 0, 1, 1);
        $this->cl->SetMaxQueryTime(100);

        $result = $this->cl->Query( $this->query, $this->table );

        $this->time += (microtime(true) - $start_time);
        $this->time_desc .= " - ".(microtime(true) - $start_time);

        if ( $result === false )
            return null;
        else
            return $result['total_found'];
    }

    public function getItems($offset, $itemCountPerPage)
    {

        global $content;
        $this->cl->SetLimits( $offset, $itemCountPerPage, MAX_RESULTS);
        $result = $this->cl->Query( $this->query, $this->table );

        $docs = array();
        if ( $result !== false  ) {
            
            if ( $this->cl->GetLastWarning() ) {
              //echo "WARNING: " . $this->cl->GetLastWarning() . "";
            }

            $this->tcount = $result["total_found"];
            $this->time = $result["time"];
            
            if (!empty($result["matches"]) ) {
                $ids = array();
                foreach ( $result["matches"] as $doc => $docinfo )
                {
                    $uri = $this->fileutils->longs2uri($docinfo["attrs"]["uri1"], $docinfo["attrs"]["uri2"], $docinfo["attrs"]["uri3"]);
                    $hexuri = $this->fileutils->uri2hex($uri);
                    $docs[$hexuri] = array();
                    $docs[$hexuri]["search"] = $docinfo['attrs'];
                    $docs[$hexuri]["search"]["id"] = $doc;
                    $ids []= new MongoId($hexuri);
                }

                $fmodel = new Model_Files();
                $files = $fmodel->getFiles( $ids );
                foreach ($files as $file) {
                    $hexuri = $file['_id']->__toString();
                    $obj = $docs[$hexuri];
                    $obj['file'] = $file;

                    $this->fileutils->chooseFilename($obj, $this->query);
                    $this->fileutils->buildSourceLinks($obj);
                    $this->fileutils->chooseType($obj, $this->type);
                    $docs[$hexuri] = $obj;
                }

                foreach ($docs as $hexuri => $doc)
                {
                    if (!isset($doc['file']) || $doc['file']['bl']!=0) {
                        $this->cl->UpdateAttributes("idx_files", array("bl"), array($doc["search"]["id"] => array(3)));
                        $this->tcount--;
                        unset($docs[$hexuri]);
                    }
                }
            }
        }
        return $docs;
    }

    public function count()
    {
        return min($this->tcount, MAX_RESULTS);
    }
}

class SearchController extends Zend_Controller_Action {

    public function init() {
         //validate domain foofind
        $this->_helper->checkdomain->check();
        
        $this->_flashMessenger = $this->_helper->getHelper ( 'FlashMessenger' );
        $this->view->mensajes = $this->_flashMessenger->getMessages ();
        
    }

    public function indexAction() {
        $this->view->lang = $this->_helper->checklang->check();
        
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
        $src2 = ($src=='')?'wftge':$src;
        $srcs['ed2k'] = (strpos($src2, 'e')===false)?$src.'e':str_replace('e', '', $src2);
        $srcs['gnutella'] = (strpos($src2, 'g')===false)?$src.'g':str_replace('g', '', $src2);
        $srcs['torrent'] = (strpos($src2, 't')===false)?$src.'t':str_replace('t', '', $src2);
        $srcs['web'] = (strpos($src2, 'w')===false)?$src.'w':str_replace('w', '', $src2);
        $srcs['ftp'] = (strpos($src2, 'f')===false)?$src.'f':str_replace('f', '', $src2);

        require_once APPLICATION_PATH.'/views/helpers/QueryString_View_Helper.php';
        require_once APPLICATION_PATH.'/views/helpers/FileUtils_View_Helper.php';

        $conds = array('q'=>trim($q), 'src'=>$src2, 'opt'=>$opt, 'type'=>$type, 'size' => $size, 'year' => $year, 'brate' => $brate, 'page' => $page);

        $helper = new QueryString_View_Helper();
        $helper->setParams($conds);
        
        $this->view->registerHelper($helper, 'qs');
        $this->view->src = $srcs;
        $this->view->qs = $conds;
        
        $helper = new FileUtils_View_Helper();
        $helper->registerHelper($this->view);

        if ($page>MAX_RESULTS/10)
        {
            $this->_helper->_flashMessenger->addMessage ( $this->view->translate ( 'Foofind does not allow browse results after page 1000.' ) );
            $this->_redirect("/{$this->view->lang}/search/".$helper->qs(array(), array('page'=>1)));
            return;
        }


        // memcached results !!!!
        $oBackend = new Zend_Cache_Backend_Memcached(
                array(
                        'servers' => array( array(
                                'host' => '127.0.0.1',
                                'port' => '11211'
                        ) ),
                        'compression' => true
        ) );

        $oFrontend = new Zend_Cache_Core(
                array(
                        'caching' => true,
                        'lifetime' => 3600,
                        'cache_id_prefix' => 'foofy_search',
                        'automatic_serialization' => true,

                ) );

        // build a caching object
        $oCache = Zend_Cache::factory( $oFrontend, $oBackend );

        $key =  md5("m$q s$src2 o$opt t$type s$size y$year b$brate p$page").$this->lang;
        $existsCache = $oCache->test($key);
        if  ( true || !$existsCache  ) {

                $SphinxPaginator = new Sphinx_Paginator('idx_files', $this->_helper->fileutils);
                $SphinxPaginator->setFilters($conds);
                $paginator = new Zend_Paginator($SphinxPaginator);

                $paginator->setDefaultScrollingStyle('Elastic');
                $paginator->setItemCountPerPage(10);
                $paginator->setCurrentPageNumber($page);
                $paginator->getCurrentItems();

                $paginator->tcount = $SphinxPaginator->tcount;
                $paginator->time = $SphinxPaginator->time;
                $paginator->time_desc = $SphinxPaginator->time_desc;
                if ($conds['type']!=null && $SphinxPaginator->count()==0)
                {
                    $conds['type']=null;
                    $SphinxPaginator->setFilters($conds);
                    $paginator->noTypeCount = $SphinxPaginator->justCount();
                 }

                $oCache->save( $paginator, $key );
            } else {
                //cache hit, load from memcache.
                $paginator = $oCache->load( $key  );

        }

        $this->view->info = array('total'=>$paginator->tcount, 'time_desc'=>$paginator->time_desc, 'time'=>$paginator->time, 'q' => $q, 'start' => 1+($page-1)*10, 'end' => min($paginator->tcount, $page*10), 'notypecount' => $paginator->noTypeCount);
        $this->view->paginator = $paginator;

        $jquery = $this->view->jQuery();

        // get current jQuery handler based on noConflict settings
        $jqHandler = ZendX_JQuery_View_Helper_JQuery::getJQueryHandler();
        $onload = '("#show_options").click(function() '
                  . '{'
                  . '   active = $("#show_options").attr("active")=="1";'
                  . '   switchOptions(active, true);'
                  . '});'
                  . ' switchOptions('.($opt?'false':'true').', false);';

        $function = 'function switchOptions(active, fade) {'
                  . '   if (active) {'
                  . '       $("#results").removeClass("padding");'
                  . '       $("#advsearch").toggle(false);'
                  . '       $("#show_options").text("'.$this->view->translate('advanced search').'");'
                  . '   } else {'
                  . '       $("#results").addClass("padding");'
                  . '       if (fade) $("#advsearch").fadeIn(); else $("#advsearch").toggle(true);'
                  . '       $("#show_options").text("'.$this->view->translate('hide advanced search').'");'
                  . '   } $("#show_options").attr("active", 1-(active?1:0));'
                  . '}';
        $jquery->addJavascript($function);
        $jquery->addOnload($jqHandler . $onload);

       

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

